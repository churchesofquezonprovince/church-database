<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Account')
                    ->description('Create or update login access for the church database.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('person_id')
                            ->label('Linked Person')
                            ->relationship('person', 'lastname')
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\Person $record): string =>
                                $record->lastname . ', ' . $record->firstname
                                . (filled($record->middlename) ? ' ' . $record->middlename : '')
                                . (filled($record->suffix) ? ' ' . $record->suffix : '')
                                . ' — ' . ($record->localityRecord?->name ?? 'No locality')
                                . ' (#' . $record->id . ')'
                            )
                            ->searchable(['firstname', 'middlename', 'lastname'])
                            ->nullable()
                            ->exists('persons', 'id')
                            ->placeholder('No linked person')
                            ->helperText('Choose this user’s existing People record. Their locality becomes the default on Attendance Dashboard, Home Meeting Schedule, and Prayer Meeting Items. They can still choose another locality.'),

                        Select::make('role')
                            ->label('Role')
                            ->options([
                                User::ROLE_ADMIN => 'Admin',
                                User::ROLE_ENCODER => 'Encoder',
                                User::ROLE_VIEWER => 'Viewer',
                            ])
                            ->default(User::ROLE_VIEWER)
                            ->required(),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText('Leave blank when editing if you do not want to change the password.'),
                    ])
                    ->columns(2),
            ]);
    }
}
