<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Google\Client;
use Google\Service\Calendar as CalendarService;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Exception as GoogleServiceException;
use RuntimeException;

class GoogleCalendarService
{
    public function enabled(): bool
    {
        return (bool) config('services.google_calendar.enabled')
            && filled(config('services.google_calendar.calendar_id'))
            && filled(config('services.google_calendar.credentials_path'));
    }

    public function upsert(Schedule $schedule): void
    {
        if (! $this->enabled()) {
            return;
        }

        $calendarId = $this->calendarId();
        $service = $this->calendarService();
        $event = $this->googleEventFromSchedule($schedule);

        try {
            if (filled($schedule->google_event_id)) {
                $googleEvent = $service->events->update(
                    $calendarId,
                    $schedule->google_event_id,
                    $event
                );
            } else {
                $googleEvent = $service->events->insert($calendarId, $event);
            }
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() !== 404) {
                throw $exception;
            }

            $googleEvent = $service->events->insert($calendarId, $event);
        }

        $schedule->forceFill([
            'google_calendar_id' => $calendarId,
            'google_event_id' => $googleEvent->getId(),
            'google_etag' => $googleEvent->getEtag(),
            'google_sync_status' => 'synced',
            'google_sync_error' => null,
            'synced_at' => now(),
        ])->saveQuietly();
    }

    public function delete(Schedule $schedule): void
    {
        if (! $this->enabled() || blank($schedule->google_event_id)) {
            return;
        }

        try {
            $this->calendarService()->events->delete(
                $this->calendarId(),
                $schedule->google_event_id
            );
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() !== 404) {
                throw $exception;
            }
        }
    }

    public function pull(): array
    {
        if (! $this->enabled()) {
            return [
                'enabled' => false,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'skipped' => 0,
            ];
        }

        $calendarId = $this->calendarId();
        $service = $this->calendarService();

        $stats = [
            'enabled' => true,
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
        ];

        $pageToken = null;

        do {
            $params = [
                'timeMin' => now()->subMonths(3)->startOfDay()->toRfc3339String(),
                'timeMax' => now()->addYear()->endOfDay()->toRfc3339String(),
                'singleEvents' => true,
                'showDeleted' => true,
                'maxResults' => 2500,
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $events = $service->events->listEvents($calendarId, $params);

            foreach ($events->getItems() ?? [] as $googleEvent) {
                $result = $this->pullGoogleEvent($googleEvent, $calendarId);

                if (array_key_exists($result, $stats)) {
                    $stats[$result]++;
                } else {
                    $stats['skipped']++;
                }
            }

            $pageToken = $events->getNextPageToken();
        } while ($pageToken);

        return $stats;
    }

    private function pullGoogleEvent(GoogleCalendarEvent $googleEvent, string $calendarId): string
    {
        $googleEventId = $googleEvent->getId();

        if (blank($googleEventId)) {
            return 'skipped';
        }

        $schedule = Schedule::query()
            ->where('google_event_id', $googleEventId)
            ->first();

        if ($googleEvent->getStatus() === 'cancelled') {
            if (! $schedule) {
                return 'skipped';
            }

            Schedule::withoutEvents(function () use ($schedule): void {
                $schedule->delete();
            });

            return 'deleted';
        }

        $startsAt = $this->dateTimeFromGoogleDateTime($googleEvent->getStart());

        if (! $startsAt) {
            return 'skipped';
        }

        $isNew = false;

        if (! $schedule) {
            $schedule = new Schedule();
            $isNew = true;
        }

        $isAllDay = filled($googleEvent->getStart()?->getDate())
            && blank($googleEvent->getStart()?->getDateTime());

        $endsAt = $this->dateTimeFromGoogleDateTime($googleEvent->getEnd());

        if ($isAllDay && $endsAt) {
            $endsAt = $endsAt->subDay()->startOfDay();
        }

        $schedule->forceFill([
            'title' => $googleEvent->getSummary() ?: '(No title)',
            'description' => $this->cleanGoogleDescription($googleEvent->getDescription()),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_all_day' => $isAllDay,
            'location' => $googleEvent->getLocation(),
            'source' => $schedule->exists ? $schedule->source : 'google',
            'google_calendar_id' => $calendarId,
            'google_event_id' => $googleEventId,
            'google_etag' => $googleEvent->getEtag(),
            'google_sync_status' => 'synced',
            'google_sync_error' => null,
            'synced_at' => now(),
        ])->saveQuietly();

        return $isNew ? 'created' : 'updated';
    }

    private function googleEventFromSchedule(Schedule $schedule): GoogleCalendarEvent
    {
        $event = new GoogleCalendarEvent();

        $event->setSummary($schedule->title);
        $event->setDescription($this->descriptionForSchedule($schedule));

        if (filled($schedule->location)) {
            $event->setLocation($schedule->location);
        }

        if ($schedule->is_all_day) {
            $start = new EventDateTime();
            $start->setDate($schedule->starts_at->toDateString());

            $endDate = ($schedule->ends_at ?? $schedule->starts_at)
                ->copy()
                ->addDay()
                ->toDateString();

            $end = new EventDateTime();
            $end->setDate($endDate);

            $event->setStart($start);
            $event->setEnd($end);

            return $event;
        }

        $timezone = config('app.timezone', 'UTC');

        $start = new EventDateTime();
        $start->setDateTime($schedule->starts_at->toRfc3339String());
        $start->setTimeZone($timezone);

        $end = new EventDateTime();
        $end->setDateTime(
            ($schedule->ends_at ?? $schedule->starts_at->copy()->addHour())
                ->toRfc3339String()
        );
        $end->setTimeZone($timezone);

        $event->setStart($start);
        $event->setEnd($end);

        return $event;
    }

    private function descriptionForSchedule(Schedule $schedule): string
    {
        return collect([
            $schedule->description,
            filled($schedule->category) ? 'Category: ' . $schedule->category : null,
            filled($schedule->locality) ? 'Locality: ' . $schedule->locality : null,
            'Source: COQP Database Schedule #' . $schedule->id,
        ])
            ->filter()
            ->implode("\n\n");
    }

    private function cleanGoogleDescription(?string $description): ?string
    {
        if (blank($description)) {
            return null;
        }

        return trim((string) preg_replace(
            "/\n{0,2}Source: COQP Database Schedule #\d+/i",
            '',
            $description
        ));
    }

    private function dateTimeFromGoogleDateTime(?EventDateTime $dateTime): ?CarbonImmutable
    {
        if (! $dateTime) {
            return null;
        }

        $value = $dateTime->getDateTime() ?: $dateTime->getDate();

        if (blank($value)) {
            return null;
        }

        return CarbonImmutable::parse($value, config('app.timezone', 'UTC'))
            ->timezone(config('app.timezone', 'UTC'));
    }

    private function calendarService(): CalendarService
    {
        $client = new Client();
        $client->setApplicationName(config('app.name', 'COQP Database') . ' Calendar Sync');
        $client->setAuthConfig($this->credentialsPath());
        $client->addScope(CalendarService::CALENDAR);

        return new CalendarService($client);
    }

    private function calendarId(): string
    {
        $calendarId = (string) config('services.google_calendar.calendar_id');

        if ($calendarId === '') {
            throw new RuntimeException('GOOGLE_CALENDAR_ID is not configured.');
        }

        return $calendarId;
    }

    private function credentialsPath(): string
    {
        $path = (string) config('services.google_calendar.credentials_path');

        if ($path === '') {
            throw new RuntimeException('GOOGLE_CALENDAR_CREDENTIALS_PATH is not configured.');
        }

        if (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            throw new RuntimeException('Google Calendar credentials file not found: ' . $path);
        }

        return $path;
    }
}
