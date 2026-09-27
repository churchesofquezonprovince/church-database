<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\AttentionNotificationService;
use Filament\Livewire\DatabaseNotifications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class AttentionDatabaseNotifications extends DatabaseNotifications
{
    protected bool $attentionSynced = false;

    public function getNotificationsQuery(): Builder | Relation
    {
        $user = $this->getUser();
        if (! $this->attentionSynced && $user instanceof User) {
            $this->attentionSynced = true;
            app(AttentionNotificationService::class)->sync($user);
        }
        return parent::getNotificationsQuery()->where(function ($query): void {
            $query->where('type', '!=', AttentionNotificationService::TYPE)
                ->orWhereNull('data->attention->dismissed')
                ->orWhere('data->attention->dismissed', false);
        });
    }

    #[On('notificationClosed')]
    public function removeNotification(string $id): void
    {
        if (! Str::isUuid($id)) { return; }
        $user = $this->getUser();
        abort_unless($user instanceof User, 401);
        app(AttentionNotificationService::class)->dismiss($user, $id);
    }

    public function clearNotifications(): void
    {
        $user = $this->getUser();
        abort_unless($user instanceof User, 401);
        foreach ($this->getNotificationsQuery()->pluck('id') as $id) {
            app(AttentionNotificationService::class)->dismiss($user, $id);
        }
    }
}
