<?php

namespace App\Filament\Pages;

use App\Services\GoogleIntegrationSettings;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ChildrenWorkDashboard extends Page
{
    protected string $view = 'filament.pages.children-work-dashboard';

    public function getTitle(): string
    {
        return "Children's Work Dashboard";
    }

    public static function getNavigationLabel(): string
    {
        return "Children's Work Dashboard";
    }

    public static function getNavigationGroup(): ?string
    {
        return "Children's Work";
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-heart';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public function googleSheetUrl(): ?string
    {
        $spreadsheetId = trim(
            (string) GoogleIntegrationSettings::get(
                'children_work.google_sheets.spreadsheet_id'
            )
        );

        if (
            $spreadsheetId === ''
            || ! preg_match(
                '/^[A-Za-z0-9_-]+$/',
                $spreadsheetId
            )
        ) {
            return null;
        }

        return 'https://docs.google.com/spreadsheets/d/'
            . $spreadsheetId
            . '/edit';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::SixExtraLarge;
    }
}