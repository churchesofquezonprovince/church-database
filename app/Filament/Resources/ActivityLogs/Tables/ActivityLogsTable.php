<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Models\ActivityLog;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date / Time')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        str_contains((string) $state, 'approved') => 'success',
                        str_contains((string) $state, 'rejected') => 'danger',
                        str_contains((string) $state, 'created') => 'success',
                        str_contains((string) $state, 'updated') => 'warning',
                        str_contains((string) $state, 'deleted') => 'danger',
                        str_contains((string) $state, 'imported') => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject_name')
                    ->label('Subject')
                    ->placeholder('No subject')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_agent')
                    ->label('Device / Browser')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(fn (): array => ActivityLog::query()
                        ->whereNotNull('action')
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->toArray())
                    ->searchable(),

                Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),

                Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->startOfWeek())),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (ActivityLog $record): string => 'Activity Log #' . $record->id)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (ActivityLog $record): HtmlString => self::detailsHtml($record)),
            ]);
    }

    private static function detailsHtml(ActivityLog $record): HtmlString
    {
        return new HtmlString(
            '<div class="space-y-5">'
            . self::section('Summary', [
                'Date / Time' => optional($record->created_at)->format('Y-m-d H:i:s'),
                'User' => $record->user?->name ?? 'System',
                'Action' => $record->action,
                'Subject' => $record->subject_name ?? 'No subject',
                'Description' => $record->description ?? '',
                'IP Address' => $record->ip_address ?? '',
            ])
            . self::jsonSection('Old Values', $record->old_values)
            . self::jsonSection('New Values', $record->new_values)
            . '</div>'
        );
    }

    private static function section(string $title, array $items): string
    {
        $html = '<div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">';
        $html .= '<p class="mb-3 font-bold text-gray-900 dark:text-white">' . e($title) . '</p>';
        $html .= '<div class="space-y-2">';

        foreach ($items as $label => $value) {
            $html .= '<div class="grid gap-1 sm:grid-cols-3">';
            $html .= '<div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">' . e($label) . '</div>';
            $html .= '<div class="sm:col-span-2 text-sm text-gray-800 dark:text-gray-200">' . e((string) $value) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    private static function jsonSection(string $title, ?array $values): string
    {
        if (blank($values)) {
            return self::section($title, [
                'Values' => 'None',
            ]);
        }

        return '<div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">'
            . '<p class="mb-3 font-bold text-gray-900 dark:text-white">' . e($title) . '</p>'
            . '<pre class="max-h-80 overflow-auto rounded-lg bg-white p-3 text-xs text-gray-800 dark:bg-gray-950 dark:text-gray-200">'
            . e(json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            . '</pre>'
            . '</div>';
    }
}
