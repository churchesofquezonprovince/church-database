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
        return (bool) \App\Services\GoogleIntegrationSettings::get('services.google_calendar.enabled')
            && filled(\App\Services\GoogleIntegrationSettings::get('services.google_calendar.credentials_path'))
            && $this->configuredCalendars() !== [];
    }

    public function configuredCalendars(): array
    {
        $calendars = [];

        foreach ((array) \App\Services\GoogleIntegrationSettings::get('services.google_calendar.calendars', []) as $key => $calendar) {
            $id = trim((string) ($calendar['id'] ?? ''));

            if ($id === '') {
                continue;
            }

            $calendars[(string) $key] = [
                'key' => (string) $key,
                'name' => (string) ($calendar['name'] ?? $key),
                'id' => $id,
            ];
        }

        $legacyId = trim((string) \App\Services\GoogleIntegrationSettings::get('services.google_calendar.calendar_id'));

        if ($legacyId !== '' && ! collect($calendars)->contains(fn (array $calendar): bool => $calendar['id'] === $legacyId)) {
            $calendars['default'] = [
                'key' => 'default',
                'name' => 'Default Calendar',
                'id' => $legacyId,
            ];
        }

        return $calendars;
    }

    public function calendarOptions(): array
    {
        return collect($this->configuredCalendars())
            ->mapWithKeys(fn (array $calendar): array => [
                $calendar['id'] => $calendar['name'],
            ])
            ->all();
    }

    public function upsert(Schedule $schedule): void
    {
        if (! $this->enabled()) {
            return;
        }

        $calendarId = $this->calendarIdForSchedule($schedule);
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
                $this->calendarIdForSchedule($schedule),
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
                'calendars' => 0,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'skipped' => 0,
            ];
        }

        $service = $this->calendarService();
        $windowStart = now()->subMonths(3)->startOfDay();
        $windowEnd = now()->addYear()->endOfDay();

        $stats = [
            'enabled' => true,
            'calendars' => 0,
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
        ];

        foreach ($this->configuredCalendars() as $calendar) {
            $stats['calendars']++;

            $pageToken = null;
            $seenEventIds = [];

            do {
                $params = [
                    'timeMin' => $windowStart->toRfc3339String(),
                    'timeMax' => $windowEnd->toRfc3339String(),
                    'singleEvents' => true,
                    'showDeleted' => true,
                    'maxResults' => 2500,
                ];

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $events = $service->events->listEvents($calendar['id'], $params);

                foreach ($events->getItems() ?? [] as $googleEvent) {
                    if (filled($googleEvent->getId())) {
                        $seenEventIds[(string) $googleEvent->getId()] = true;
                    }

                    $result = $this->pullGoogleEvent($googleEvent, $calendar['id']);

                    if (array_key_exists($result, $stats)) {
                        $stats[$result]++;
                    } else {
                        $stats['skipped']++;
                    }
                }

                $pageToken = $events->getNextPageToken();
            } while ($pageToken);

            // Verify missing events before removing cancelled local copies.
            // Reconcile only after every page was fetched successfully.
            $candidates = Schedule::query()
                ->where('google_calendar_id', $calendar['id'])
                ->whereNotNull('google_event_id')
                ->where('google_event_id', '<>', '')
                ->where('starts_at', '<=', $windowEnd)
                ->where(function ($query) use ($windowStart): void {
                    $query->where('starts_at', '>=', $windowStart)
                        ->orWhere('ends_at', '>=', $windowStart);
                })
                ->get(['id', 'google_event_id']);

            foreach ($candidates as $candidate) {
                $eventId = (string) $candidate->google_event_id;

                if (isset($seenEventIds[$eventId])) {
                    continue;
                }

                try {
                    $remoteEvent = $service->events->get(
                        $calendar['id'],
                        $eventId
                    );
                } catch (GoogleServiceException $exception) {
                    // Missing or inaccessible is not proof of cancellation.
                    if (in_array((int) $exception->getCode(), [404, 410], true)) {
                        $stats['skipped']++;
                        continue;
                    }

                    throw $exception;
                }

                if (
                    $remoteEvent->getStatus() !== 'cancelled'
                    || (string) $remoteEvent->getId() !== $eventId
                ) {
                    continue;
                }

                $result = $this->pullGoogleEvent(
                    $remoteEvent,
                    $calendar['id']
                );

                if (array_key_exists($result, $stats)) {
                    $stats[$result]++;
                }
            }
        }

        return $stats;
    }

    private function pullGoogleEvent(GoogleCalendarEvent $googleEvent, string $calendarId): string
    {
        $googleEventId = $googleEvent->getId();

        if (blank($googleEventId)) {
            return 'skipped';
        }

        $schedule = Schedule::query()
#            ->where('google_calendar_id', $calendarId)
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

    private function calendarIdForSchedule(Schedule $schedule): string
    {
        $calendarId = trim((string) $schedule->google_calendar_id);

        if ($calendarId !== '') {
            return $calendarId;
        }

        $firstCalendar = collect($this->configuredCalendars())->first();

        if (! $firstCalendar) {
            throw new RuntimeException('No Google Calendar IDs are configured.');
        }

        return $firstCalendar['id'];
    }

    private function calendarService(): CalendarService
    {
        $client = new Client();
        $client->setApplicationName(config('app.name', 'COQP Database') . ' Calendar Sync');
        $client->setAuthConfig($this->credentialsPath());
        $client->addScope(CalendarService::CALENDAR);

        return new CalendarService($client);
    }

    private function credentialsPath(): string
    {
        $path = (string) \App\Services\GoogleIntegrationSettings::get('services.google_calendar.credentials_path');

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
