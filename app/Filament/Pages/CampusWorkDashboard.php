<?php

namespace App\Filament\Pages;

use App\Models\CampusWorkDashboardItem;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class CampusWorkDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Campus Work';

    protected static ?string $navigationLabel = 'Campus Work Dashboard';

    protected static ?string $title = 'Campus Work Dashboard';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.campus-work-dashboard';

    public function studentBook(): CampusWorkDashboardItem
    {
        return CampusWorkDashboardItem::query()
            ->firstOrCreate(
                [
                    'section' => CampusWorkDashboardItem::SECTION_STUDENT_BOOK,
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Bridge and Channel',
                ]
            );
    }

    public function servingBook(): CampusWorkDashboardItem
    {
        return CampusWorkDashboardItem::query()
            ->firstOrCreate(
                [
                    'section' => CampusWorkDashboardItem::SECTION_SERVING_BOOK,
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Fellowship for the beginning among the work of the students',
                ]
            );
    }

    public function additionalReadings()
    {
        return CampusWorkDashboardItem::query()
            ->where('section', CampusWorkDashboardItem::SECTION_ADDITIONAL_READING)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }
}
