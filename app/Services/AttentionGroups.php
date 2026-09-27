<?php

namespace App\Services;

use App\Filament\Pages\{AttendanceSheets, ChildrenWorkDashboard, ChildrenWorkLessons, DeveloperOptions, ExternalHymnsSetup, HymnsSetup};
use App\Models\{AttendanceMeetingResponse, AttendanceSession, AttendanceSheet, ChildrenWorkLesson, HymnAdditionRequest, ProblemReport};
use App\Support\MeetingResponseAttentionWorkflow;
use Illuminate\Support\Facades\Cache;

class AttentionGroups
{
    public const KEYS = ['hymns', 'variants', 'external_hymns', 'attendance', 'children', 'problems'];

    public function allowed(): array
    {
        return array_filter([
            'hymns' => HymnsSetup::canAccess(),
            'variants' => HymnsSetup::canAccess(),
            'external_hymns' => ExternalHymnsSetup::canAccess() && HymnsSetup::canAccess(),
            'attendance' => AttendanceSheets::canAccess(),
            'children' => ChildrenWorkLessons::canAccess(),
            'problems' => DeveloperOptions::canAccess(),
        ]);
    }

    public function get(string $key): array
    {
        // Count queries are shared, but access checks run separately for each user.
        return Cache::remember('coqp:attention:v1:'.$key, 60, fn () => $this->build($key));
    }

    protected function build(string $key): array
    {
        return match ($key) {
            'hymns' => $this->group('Hymns need review',
                (new HymnsSetup())->unresolvedEntries(false)->pluck('id')->all(),
                'hymn entries need review.', HymnsSetup::getUrl(), 'hymn entry needs review.'),
            'variants' => $this->group('Hymn variants need review',
                (new HymnsSetup())->variantReviewEntries()->pluck('id')->all(),
                'variant entries need review.', HymnsSetup::getUrl(), 'variant entry needs review.'),
            'external_hymns' => $this->group('External hymn requests',
                HymnAdditionRequest::query()->where('status', HymnAdditionRequest::STATUS_PENDING)
                    ->whereNotNull('source_url')->where('source_url', '!=', '')->pluck('id')->all(),
                'external hymn requests await review.', HymnsSetup::getUrl(), 'external hymn request awaits review.'),
            'attendance' => $this->attendance(),
            'children' => $this->children(),
            'problems' => $this->group('New problem reports',
                ProblemReport::query()->where('status', 'new')->pluck('id')->all(),
                'new reports await review.', DeveloperOptions::getUrl(), 'new report awaits review.'),
            default => throw new \InvalidArgumentException('Unknown attention category.'),
        };
    }

    protected function group(string $title, array $members, string $description, string $url, ?string $singularDescription = null): array
    {
        $members = array_values(array_unique(array_map('strval', $members)));
        sort($members, SORT_STRING);
        if (count($members) === 1 && $singularDescription !== null) {
            $description = $singularDescription;
        }
        return compact('title', 'members', 'url') + [
            'body' => number_format(count($members)).' '.$description,
        ];
    }

    protected function attendance(): array
    {
        $members = [];
        $firstSession = null;
        $sessionCount = 0;
        // Active custom sheets, and only the response form currently used by each sheet.
        foreach (AttendanceSheet::query()->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->where('is_active', true)->orderBy('id')->get() as $sheet) {
            $form = $sheet->meeting_form_type === AttendanceSheet::MEETING_FORM_GOOGLE
                ? AttendanceMeetingResponse::FORM_GOOGLE : AttendanceMeetingResponse::FORM_NORMAL;
            $sessions = AttendanceSession::query()->where('attendance_sheet_id', $sheet->id)
                ->whereHas('meetingResponses', fn ($q) => $q->where('submitted_form_type', $form))
                ->with(['meetingResponses' => fn ($q) => $q->where('submitted_form_type', $form)])
                ->orderBy('session_date')->orderBy('id')->get();
            foreach ($sessions as $session) {
                $workflows = MeetingResponseAttentionWorkflow::statuses($session->meetingResponses, $session);
                $pending = $workflows->filter(fn ($workflow) => $workflow['needs_action'] ?? false)->keys();
                if ($pending->isEmpty()) { continue; }
                $firstSession ??= $session;
                $sessionCount++;
                foreach ($pending as $id) { $members[] = (string) $id; }
            }
        }
        $sessionLabel = number_format($sessionCount).($sessionCount === 1 ? ' session.' : ' sessions.');
        return $this->group('Pre-listed responses need action', $members,
            'responses need identity or participant review across '.$sessionLabel,
            AttendanceSheets::getUrl($firstSession ? [
                'sheetId' => $firstSession->attendance_sheet_id,
                'sessionId' => $firstSession->id,
                'prelisted' => 'needs_action',
            ] : []), 'response needs identity or participant review across '.$sessionLabel);
    }

    public static function missingMaterials(ChildrenWorkLesson $lesson): array
    {
        $missing = [];
        if (blank(trim((string) $lesson->memory_verse))) { $missing[] = 'memory verse'; }
        if (blank(trim((string) $lesson->story_url))) { $missing[] = 'story link'; }
        return $missing;
    }

    protected function children(): array
    {
        $members = [];
        $details = [];
        $first = null;
        $lessons = ChildrenWorkLesson::query()->whereNotIn('status', ['draft', 'cancelled'])
            ->whereDate('scheduled_on', '>=', today())
            ->whereDate('scheduled_on', '<=', today()->addDays(7))
            ->orderBy('scheduled_on')->orderBy('id')->get();
        foreach ($lessons as $lesson) {
            $missing = static::missingMaterials($lesson);
            if ($missing === []) { continue; }
            $first ??= $lesson;
            foreach ($missing as $field) {
                $members[] = $lesson->id.':'.$lesson->scheduled_on->format('Y-m-d').':'.$field;
            }
            $details[] = $lesson->scheduled_on->format('M j').': '
                .($lesson->lesson_title ?: 'Untitled lesson').' — missing '.implode(' and ', $missing).'.';
        }
        $group = $this->group("Children’s lessons need materials", $members, '',
            ChildrenWorkDashboard::getUrl($first ? ['lesson' => $first->id] : []));
        $group['body'] = count($details)
            .(count($details) === 1 ? ' upcoming lesson needs' : ' upcoming lessons need')
            .' materials within the next 7 days. '
            .implode(' ', array_slice($details, 0, 3));
        return $group;
    }
}
