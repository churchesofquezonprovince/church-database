<?php

namespace App\Http\Controllers;

use App\Models\CampusWorkDashboardItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampusWorkDashboardController extends Controller
{
    public function updateMainBook(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'section' => [
                'required',
                Rule::in([
                    CampusWorkDashboardItem::SECTION_STUDENT_BOOK,
                    CampusWorkDashboardItem::SECTION_SERVING_BOOK,
                ]),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'link' => ['nullable', 'string', 'max:500'],
        ]);

        CampusWorkDashboardItem::query()
            ->firstOrCreate(
                [
                    'section' => $data['section'],
                    'sort_order' => 1,
                ],
                [
                    'title' => $data['title'],
                ]
            )
            ->update([
                'title' => trim($data['title']),
                'description' => $this->nullIfBlank($data['description'] ?? null),
                'link' => $this->nullIfBlank($data['link'] ?? null),
            ]);

        return back()->with('campus_work_dashboard_book_updated', true);
    }

    public function storeReading(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $this->validatedReading($request);

        $nextSortOrder = ((int) CampusWorkDashboardItem::query()
            ->where('section', CampusWorkDashboardItem::SECTION_ADDITIONAL_READING)
            ->max('sort_order')) + 1;

        CampusWorkDashboardItem::query()->create([
            'section' => CampusWorkDashboardItem::SECTION_ADDITIONAL_READING,
            'title' => trim($data['title']),
            'description' => $this->nullIfBlank($data['description'] ?? null),
            'link' => $this->nullIfBlank($data['link'] ?? null),
            'sort_order' => $nextSortOrder,
        ]);

        return back()->with('campus_work_dashboard_reading_created', true);
    }

    public function updateReading(
        Request $request,
        CampusWorkDashboardItem $item
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        abort_unless(
            $item->section === CampusWorkDashboardItem::SECTION_ADDITIONAL_READING,
            404
        );

        $data = $this->validatedReading($request);

        $item->update([
            'title' => trim($data['title']),
            'description' => $this->nullIfBlank($data['description'] ?? null),
            'link' => $this->nullIfBlank($data['link'] ?? null),
        ]);

        return back()->with('campus_work_dashboard_reading_updated', true);
    }

    public function destroyReading(
        CampusWorkDashboardItem $item
    ): RedirectResponse {
        abort_unless(auth()->user()?->canDeleteRecords(), 403);

        abort_unless(
            $item->section === CampusWorkDashboardItem::SECTION_ADDITIONAL_READING,
            404
        );

        $item->delete();

        return back()->with('campus_work_dashboard_reading_deleted', true);
    }

    private function validatedReading(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'link' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function nullIfBlank(mixed $value): ?string
    {
        return filled($value)
            ? trim((string) $value)
            : null;
    }
}
