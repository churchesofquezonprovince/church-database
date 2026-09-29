<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('person.lastname')
                    ->label('Linked Person')
                    ->formatStateUsing(fn (User $record): ?string =>
                        $record->person
                            ? $record->person->lastname . ', ' . $record->person->firstname
                                . ' (#' . $record->person->id . ')'
                            : null
                    )
                    ->placeholder('Not linked'),

                TextColumn::make('person.localityRecord.name')
                    ->label('Person Locality')
                    ->placeholder('No locality'),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_ENCODER => 'Encoder',
                        User::ROLE_VIEWER => 'Viewer',
                        default => 'Unknown',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'danger',
                        User::ROLE_ENCODER => 'warning',
                        User::ROLE_VIEWER => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_ENCODER => 'Encoder',
                        User::ROLE_VIEWER => 'Viewer',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== auth()->id()),
            ]);
    }
}
