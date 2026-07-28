<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use App\Services\GoogleCalendarService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SchedulesCalendarWidget extends CalendarWidget
{
    protected bool $dateClickEnabled = true;

    protected bool $dateSelectEnabled = true;

    protected bool $eventClickEnabled = true;

    protected ?string $defaultEventClickAction = 'edit';

    public function createScheduleAction(): CreateAction
    {
        return $this
            ->createAction(Schedule::class)
            ->label('Add Schedule')
            ->modalHeading('Add Schedule')
            ->mutateDataUsing(function (array $data): array {
                $data['source'] = 'local';
                $data['created_by'] = auth()->id();
                $data['updated_by'] = auth()->id();

                return $data;
            });
    }

    public function defaultSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),

                Select::make('google_calendar_id')
                    ->label('Google Calendar')
                    ->options(fn (): array => app(GoogleCalendarService::class)->calendarOptions())
                    ->placeholder('Use first configured calendar')
                    ->searchable()
                    ->native(false)
                    ->helperText('Used when Google Calendar sync is enabled.'),

                Select::make('category')
                    ->label('Category')
                    ->options([
                        'Church Activity' => 'Church Activity',
                        'Campus Work' => 'Campus Work',
                        'Prayer Meeting' => 'Prayer Meeting',
                        "Lord's Table" => "Lord's Table",
                        'Training' => 'Training',
                        'Conference' => 'Conference',
                        'Service Meeting' => 'Service Meeting',
                        'Other' => 'Other',
                    ])
                    ->searchable()
                    ->native(false),

                TextInput::make('locality')
                    ->label('Locality')
                    ->maxLength(255),

                TextInput::make('location')
                    ->label('Location')
                    ->maxLength(255),

                DateTimePicker::make('starts_at')
                    ->label('Starts At')
                    ->required(),

                DateTimePicker::make('ends_at')
                    ->label('Ends At'),

                Toggle::make('is_all_day')
                    ->label('All-day schedule'),

                Textarea::make('description')
                    ->label('Description / Notes')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected function getDateClickContextMenuActions(): array
    {
        return [
            $this->createScheduleAction(),
        ];
    }

    protected function getDateSelectContextMenuActions(): array
    {
        return [
            $this->createScheduleAction(),
        ];
    }

    protected function getEventClickContextMenuActions(): array
    {
        return [
            $this->viewAction(),
            $this->editAction(),
            $this->deleteAction(),
        ];
    }

    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        return Schedule::query()
            ->where(function (Builder $query) use ($info): void {
                $query
                    ->whereBetween('starts_at', [$info->start, $info->end])
                    ->orWhereBetween('ends_at', [$info->start, $info->end])
                    ->orWhere(function (Builder $query) use ($info): void {
                        $query
                            ->where('starts_at', '<=', $info->start)
                            ->where(function (Builder $query) use ($info): void {
                                $query
                                    ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $info->end);
                            });
                    });
            });
    }
}
