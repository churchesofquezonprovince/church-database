<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use App\Models\DriveMeetingDocument;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Action;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class ServiceMeetingMinutes extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.service-meeting-minutes';

    public function getTitle(): string
    {
        return 'Service Meeting Minutes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Service Meeting Minutes';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Posts';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_document')
                ->label('New Document')
                ->color('primary')
                ->icon('heroicon-o-plus')
                ->url(
                    \App\Models\DriveMeetingDocument::serviceMeetingFolderUrl(),
                    true
                ),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DriveMeetingDocument::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Document Title')
                    ->searchable()
                    ->sortable()
                    ->url(fn (DriveMeetingDocument $record) => $record->view_link)
                    ->openUrlInNewTab()
                    ->color('primary'),
                    
                TextColumn::make('created_time')
                    ->label('Meeting Date')
                    ->date('F j, Y')
                    ->sortable(),

                TextColumn::make('file_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PDF' => 'danger',
                        'Google Doc' => 'info',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_time', 'desc')
            ->defaultPaginationPageOption(20)
            ->paginated([20, 50, 100, 200]);

    }
}