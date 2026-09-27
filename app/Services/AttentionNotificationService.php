<?php

namespace App\Services;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class AttentionNotificationService
{
    public const TYPE = 'coqp.attention.v1';

    public function __construct(protected AttentionGroups $groups) {}

    public function sync(User $user): void
    {
        abort_unless(auth()->id() === $user->getKey(), 403);
        $allowed = $this->groups->allowed();
        $snapshots = [];
        foreach (AttentionGroups::KEYS as $key) {
            if (! isset($allowed[$key])) { $snapshots[$key] = null; continue; }
            try { $snapshots[$key] = $this->groups->get($key); }
            catch (\Throwable $error) {
                // A failed query is not evidence that the work has been resolved.
                report($error);
            }
        }
        DB::transaction(function () use ($user, $snapshots): void {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            foreach ($snapshots as $key => $group) {
                $id = Uuid::uuid5(Uuid::NAMESPACE_URL,
                    'coqp:attention:'.$user->getMorphClass().':'.$user->getKey().':'.$key)->toString();
                $record = $user->notifications()->whereKey($id)->first();
                if ($record && $record->type !== self::TYPE) { continue; }
                if (! $group || $group['members'] === []) {
                    $record?->delete();
                    continue;
                }
                $previous = $record?->data['attention'] ?? [];
                $newWork = ! $record || array_diff($group['members'], $previous['members'] ?? []) !== [];
                $data = Notification::make()->title($group['title'])->warning()
                    ->body(e($group['body']))
                    ->actions([Action::make('review')->label('Review')->button()
                        ->url($group['url'])->markAsRead()])->getDatabaseMessage();
                $data['attention'] = [
                    'category' => $key,
                    'members' => $group['members'],
                    'dismissed' => $newWork ? false : ($previous['dismissed'] ?? false),
                ];
                if (! $record) {
                    $user->notifications()->create(['id' => $id, 'type' => self::TYPE,
                        'data' => $data, 'read_at' => null]);
                } elseif ($record->data !== $data || $newWork) {
                    $record->data = $data;
                    if ($newWork) { $record->read_at = null; $record->created_at = now(); }
                    $record->save();
                }
            }
        });
    }

    public function dismiss(User $user, string $id): void
    {
        abort_unless(auth()->id() === $user->getKey(), 403);
        DB::transaction(function () use ($user, $id): void {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $record = $user->notifications()->whereKey($id)->first();
            if (! $record) { return; }
            if ($record->type !== self::TYPE) { $record->delete(); return; }
            $data = $record->data;
            $data['attention']['dismissed'] = true;
            $record->forceFill(['data' => $data, 'read_at' => now()])->save();
        });
    }
}
