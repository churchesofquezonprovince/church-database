<?php

namespace App\Filament\Resources\Households\Tables;

use App\Filament\Pages\FamilyTree;
use App\Models\Household;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class HouseholdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('household_name')
            ->columns([
                TextColumn::make('household_name')
                    ->label('Household')
                    ->getStateUsing(fn (Household $record): HtmlString => self::householdColumn($record))
                    ->html()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('head.display_name')
                    ->label('Head')
                    ->getStateUsing(fn (Household $record): HtmlString => self::headColumn($record))
                    ->html()
                    ->searchable(['firstname', 'lastname']),

                TextColumn::make('members_count')
                    ->label('Members')
                    ->getStateUsing(fn (Household $record): HtmlString => self::membersColumn($record))
                    ->html(),

                TextColumn::make('locality')
                    ->label('Locality')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('address')
                    ->label('Address')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::addressColumn($state))
                    ->html()
                    ->searchable(),

                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('locality')
                    ->label('Locality')
                    ->options(fn (): array => Household::query()
                        ->whereNotNull('locality')
                        ->where('locality', '!=', '')
                        ->distinct()
                        ->orderBy('locality')
                        ->pluck('locality', 'locality')
                        ->toArray())
                    ->searchable(),

                SelectFilter::make('missing_data')
                    ->label('Missing Data')
                    ->options([
                        'no_head' => 'No Household Head',
                        'no_locality' => 'No Locality',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        if ($value === 'no_head') {
                            return $query->whereNull('household_head_id');
                        }

                        if ($value === 'no_locality') {
                            return $query->where(function ($query): void {
                                $query->whereNull('locality')
                                    ->orWhere('locality', '');
                            });
                        }

                        return $query;
                    }),

            ])

            ->recordActions([
                Action::make('viewHeadFamilyTree')
                    ->label("Head's Family Tree")
                    ->icon('heroicon-o-user-group')
                    ->visible(fn (Household $record): bool => filled($record->household_head_id))
                    ->url(fn (Household $record): string => FamilyTree::getUrl([
                        'personId' => $record->household_head_id,
                    ])),

                ViewAction::make(),
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function householdColumn(Household $record): HtmlString
    {
        $membersCount = $record->members()->count();

        return new HtmlString(
            '<div class="flex items-center gap-3">'
            . '<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white shadow-sm">HH</div>'
            . '<div>'
            . '<div class="font-bold text-gray-900 dark:text-white">' . e($record->household_name ?: 'Unnamed Household') . '</div>'
            . '<div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">' . e((string) $membersCount) . ' member(s)</div>'
            . '</div>'
            . '</div>'
        );
    }

    private static function headColumn(Household $record): HtmlString
    {
        if (! $record->head) {
            return new HtmlString(
                '<span class="rounded-lg border border-dashed border-gray-300 px-2.5 py-1 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">No head</span>'
            );
        }

        return new HtmlString(
            '<span class="inline-flex rounded-lg border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
            . e($record->head->display_name)
            . '</span>'
        );
    }

    private static function membersColumn(Household $record): HtmlString
    {
        $count = $record->members()->count();

        return new HtmlString(
            '<span class="inline-flex rounded-xl border border-primary-200 bg-primary-50 px-3 py-1 text-sm font-bold text-primary-700 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
            . e((string) $count)
            . '</span>'
        );
    }

    private static function addressColumn(?string $address): HtmlString
    {
        if (blank($address)) {
            return new HtmlString(
                '<span class="text-sm text-gray-500 dark:text-gray-400">Not recorded</span>'
            );
        }

        return new HtmlString(
            '<span class="text-sm font-medium text-gray-700 dark:text-gray-200">'
            . e(str($address)->limit(45))
            . '</span>'
        );
    }
}
