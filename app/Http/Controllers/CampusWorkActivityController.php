<?php

namespace App\Http\Controllers;

use App\Filament\Pages\CampusActivities;
use App\Models\CampusWorkActivity;
use App\Models\School;
use App\Support\LocalityOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampusWorkActivityController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $this->validatedData($request);

        $activity = CampusWorkActivity::query()->create(
            $this->normalizedData($data)
        );

        return redirect()
            ->to(
                CampusActivities::getUrl()
                . '?'
                . http_build_query([
                    'termId' => $activity->campus_work_term_id,
                ])
            )
            ->with('campus_activity_created', true);
    }

    public function update(
        Request $request,
        CampusWorkActivity $activity
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $this->validatedData($request);

        $activity->update(
            $this->normalizedData($data)
        );

        return back()->with('campus_activity_updated', true);
    }

    public function destroy(
        CampusWorkActivity $activity
    ): RedirectResponse {
        abort_unless(auth()->user()?->canDeleteRecords(), 403);

        $activity->delete();

        return back()->with('campus_activity_deleted', true);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'campus_work_term_id' => [
                'nullable',
                'integer',
                'exists:campus_work_terms,id',
            ],

            'activity_type' => [
                'required',
                'string',
                Rule::in(array_keys(CampusWorkActivity::typeOptions())),
            ],

            'other_activity_name' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $request->input('activity_type')
                        === CampusWorkActivity::TYPE_OTHER_ACTIVITY
                ),
                'nullable',
                'string',
                'max:150',
            ],

            'title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'activity_date' => [
                'required',
                'date',
            ],

            'start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'end_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'school_id' => [
                'nullable',
                'integer',
                'exists:schools,id',
            ],

            'venue' => [
                'nullable',
                'string',
                'max:255',
            ],

            'locality_id' => [
                'nullable',
                'integer',
                'exists:localities,id',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);
    }

    private function normalizedData(array $data): array
    {
        $activityType = $data['activity_type'];

        $locality = null;

        if (filled($data['locality_id'] ?? null)) {
            $locality = LocalityOptions::activeConfiguredLocality(
                (int) $data['locality_id']
            );

            if (! $locality) {
                throw ValidationException::withMessages([
                    'locality_id' =>
                        'Select an active configured Locality.',
                ]);
            }
        }

        $school = null;

        if (filled($data['school_id'] ?? null)) {
            $school = School::query()
                ->whereKey((int) $data['school_id'])
                ->where('is_active', true)
                ->first();

            if (! $school) {
                throw ValidationException::withMessages([
                    'school_id' =>
                        'Select an active configured School.',
                ]);
            }
        }

        return [
            'campus_work_term_id' => $data['campus_work_term_id'] ?? null,

            'activity_type' => $activityType,

            'other_activity_name' =>
                $activityType === CampusWorkActivity::TYPE_OTHER_ACTIVITY
                    ? $this->nullIfBlank($data['other_activity_name'] ?? null)
                    : null,

            'title' => $this->nullIfBlank($data['title'] ?? null),

            'activity_date' => $data['activity_date'],

            'start_time' => $this->nullIfBlank($data['start_time'] ?? null),

            'end_time' => $this->nullIfBlank($data['end_time'] ?? null),

            'school_id' => $school?->id,

            'school_campus' => $school?->name,

            'venue' => $this->nullIfBlank($data['venue'] ?? null),

            'locality_id' => $locality?->id,

            'locality' => $locality?->name,

            'description' => $this->nullIfBlank(
                $data['description'] ?? null
            ),
        ];
    }

    private function nullIfBlank(mixed $value): ?string
    {
        return filled($value)
            ? trim((string) $value)
            : null;
    }
}
