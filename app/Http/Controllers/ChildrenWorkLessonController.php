<?php

namespace App\Http\Controllers;

use App\Filament\Pages\ChildrenWorkLessons;
use App\Models\ChildrenWorkLesson;
use App\Services\ChildrenWorkGoogleSheetsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChildrenWorkLessonController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager();

        ChildrenWorkLesson::query()->create($this->validatedData($request));

        return redirect(ChildrenWorkLessons::getUrl())
            ->with('children_work_saved', 'Lesson created.');
    }

    public function update(Request $request, ChildrenWorkLesson $lesson): RedirectResponse
    {
        $this->authorizeManager();

        $lesson->update($this->validatedData($request));

        return redirect(ChildrenWorkLessons::getUrl())
            ->with('children_work_saved', 'Lesson updated.');
    }

    public function destroy(ChildrenWorkLesson $lesson): RedirectResponse
    {
        $this->authorizeManager();

        $lesson->delete();

        return redirect(ChildrenWorkLessons::getUrl())
            ->with('children_work_saved', 'Lesson deleted.');
    }

    public function syncGoogleSheet(ChildrenWorkGoogleSheetsService $service): RedirectResponse
    {
        $this->authorizeManager();

        try {
            $result = $service->syncFromGoogleSheet();

            return redirect(ChildrenWorkLessons::getUrl())
                ->with(
                    'children_work_saved',
                    'Google Sheet synced. Created: ' . $result['created']
                    . '. Updated: ' . $result['updated']
                    . '. Skipped: ' . $result['skipped']
                    . '. Sheet: ' . $result['sheet_title'] . '.'
                );
        } catch (\Throwable $exception) {
            return redirect(ChildrenWorkLessons::getUrl())
                ->with('children_work_error', 'Google Sheet sync failed: ' . $exception->getMessage());
        }
    }

    public function pushGoogleSheet(ChildrenWorkGoogleSheetsService $service): RedirectResponse
    {
        $this->authorizeManager();

        try {
            $result = $service->pushToGoogleSheet();

            return redirect(ChildrenWorkLessons::getUrl())
                ->with(
                    'children_work_saved',
                    'Website changes pushed to Google Sheet. Created: ' . $result['created']
                    . '. Updated: ' . $result['updated']
                    . '. Failed: ' . $result['failed']
                    . '. Sheet: ' . $result['sheet_title'] . '.'
                );
        } catch (\Throwable $exception) {
            return redirect(ChildrenWorkLessons::getUrl())
                ->with('children_work_error', 'Google Sheet push failed: ' . $exception->getMessage());
        }
    }

    private function authorizeManager(): void
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'scheduled_on' => ['nullable', 'date'],
            'lesson_code' => ['nullable', 'string', 'max:255'],
            'lesson_title' => ['nullable', 'string'],
            'lesson_url' => ['nullable', 'string'],
            'suggested_hymn' => ['nullable', 'string'],
            'suggested_hymn_url' => ['nullable', 'string'],
            'memory_verse' => ['nullable', 'string'],
            'story' => ['nullable', 'string'],
            'presentation_slides' => ['nullable', 'string'],
            'presentation_slides_url' => ['nullable', 'string'],
            'activity' => ['nullable', 'string'],
            'activity_url' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = trim($value) === '' ? null : trim($value);
            }
        }

        $validated['status'] = $validated['status'] ?: 'scheduled';
        $validated['source'] = 'local';
        $validated['sync_status'] = 'local';

        return $validated;
    }
}
