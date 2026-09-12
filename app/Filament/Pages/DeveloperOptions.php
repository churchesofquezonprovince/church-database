<?php

namespace App\Filament\Pages;

use App\Models\NavigationSearchAlias;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DeveloperOptions extends Page
{
    protected string $view = 'filament.pages.developer-options';

    protected static ?string $slug = 'developer-options';

    public string $phrase = '';

    public string $targetType = 'item';

    public string $targetKey = '';

    public bool $isActive = true;

    public static function getNavigationLabel(): string
    {
        return 'Developer Options';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public static function getNavigationSort(): ?int
    {
        return 91;
    }

    public function getTitle(): string
    {
        return 'Developer Options';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );
    }

    public function updatedTargetType(): void
    {
        $this->targetKey = '';
    }

    public function aliases(): Collection
    {
        return NavigationSearchAlias::query()
            ->orderBy('phrase')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function navigationTargetOptions(): array
    {
        $options = [];

        foreach (filament()->getNavigation() as $group) {
            $groupLabel = trim(
                (string) ($group->getLabel() ?? '')
            );

            if ($groupLabel === '') {
                $groupLabel = 'Navigation';
            }

            if ($this->targetType === 'group') {
                $key = $this->encodeTarget([
                    'type' => 'group',
                    'group' => null,
                    'label' => $groupLabel,
                ]);

                $options[$key] = $groupLabel;

                continue;
            }

            foreach ($group->getItems() as $item) {
                $this->appendNavigationItemOption(
                    options: $options,
                    item: $item,
                    groupLabel: $groupLabel,
                );
            }
        }

        asort(
            $options,
            SORT_NATURAL | SORT_FLAG_CASE
        );

        return $options;
    }

    public function createAlias(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $data = $this->validate([
            'phrase' => [
                'required',
                'string',
                'max:150',
            ],
            'targetType' => [
                'required',
                'in:item,group',
            ],
            'targetKey' => [
                'required',
                'string',
            ],
            'isActive' => [
                'boolean',
            ],
        ]);

        $availableTargets =
            $this->navigationTargetOptions();

        if (
            ! array_key_exists(
                $data['targetKey'],
                $availableTargets
            )
        ) {
            throw ValidationException::withMessages([
                'targetKey' =>
                    'Select a target from the current navigation.',
            ]);
        }

        $target =
            $this->decodeTarget(
                $data['targetKey']
            );

        if (
            ! $target
            || $target['type']
                !== $data['targetType']
        ) {
            throw ValidationException::withMessages([
                'targetKey' =>
                    'The selected navigation target is invalid.',
            ]);
        }

        $phrase = trim($data['phrase']);

        $duplicate =
            NavigationSearchAlias::query()
                ->whereRaw(
                    'LOWER(phrase) = ?',
                    [mb_strtolower($phrase)]
                )
                ->where(
                    'target_type',
                    $target['type']
                )
                ->where(
                    'target_label',
                    $target['label']
                )
                ->when(
                    filled($target['group']),
                    fn ($query) =>
                        $query->where(
                            'target_group',
                            $target['group']
                        ),
                    fn ($query) =>
                        $query->whereNull(
                            'target_group'
                        )
                )
                ->exists();

        if ($duplicate) {
            Notification::make()
                ->title('Alias already exists')
                ->body(
                    'That search phrase already points to this navigation target.'
                )
                ->warning()
                ->send();

            return;
        }

        $sortOrder =
            (
                (int) NavigationSearchAlias::query()
                    ->max('sort_order')
            ) + 10;

        NavigationSearchAlias::query()->create([
            'phrase' => $phrase,
            'target_type' => $target['type'],
            'target_group' => $target['group'],
            'target_label' => $target['label'],
            'is_active' => (bool) $data['isActive'],
            'sort_order' => $sortOrder,
        ]);

        $this->reset(
            'phrase',
            'targetKey'
        );

        $this->isActive = true;

        Notification::make()
            ->title('Navigation search alias added')
            ->success()
            ->send();
    }

    public function toggleAlias(int $aliasId): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $alias =
            NavigationSearchAlias::query()
                ->findOrFail($aliasId);

        $alias->update([
            'is_active' =>
                ! $alias->is_active,
        ]);

        Notification::make()
            ->title(
                $alias->is_active
                    ? 'Alias enabled'
                    : 'Alias disabled'
            )
            ->success()
            ->send();
    }

    public function deleteAlias(int $aliasId): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        NavigationSearchAlias::query()
            ->findOrFail($aliasId)
            ->delete();

        Notification::make()
            ->title('Navigation search alias deleted')
            ->success()
            ->send();
    }

    private function appendNavigationItemOption(
        array &$options,
        mixed $item,
        string $groupLabel,
        ?string $parentLabel = null
    ): void {
        if (! $item->isVisible()) {
            return;
        }

        $label = trim(
            (string) $item->getLabel()
        );

        if ($label !== '') {
            $key = $this->encodeTarget([
                'type' => 'item',
                'group' => $groupLabel,
                'label' => $label,
            ]);

            $display =
                $groupLabel
                . ' → '
                . (
                    filled($parentLabel)
                        ? $parentLabel . ' → '
                        : ''
                )
                . $label;

            $options[$key] = $display;
        }

        foreach ($item->getChildItems() as $child) {
            $this->appendNavigationItemOption(
                options: $options,
                item: $child,
                groupLabel: $groupLabel,
                parentLabel: $label,
            );
        }
    }

    private function encodeTarget(array $target): string
    {
        return base64_encode(
            json_encode(
                $target,
                JSON_THROW_ON_ERROR
            )
        );
    }

    private function decodeTarget(
        string $key
    ): ?array {
        $decoded =
            base64_decode(
                $key,
                true
            );

        if ($decoded === false) {
            return null;
        }

        $target =
            json_decode(
                $decoded,
                true
            );

        if (
            ! is_array($target)
            || ! isset(
                $target['type'],
                $target['label']
            )
        ) {
            return null;
        }

        return [
            'type' => (string) $target['type'],
            'group' =>
                filled($target['group'] ?? null)
                    ? (string) $target['group']
                    : null,
            'label' => (string) $target['label'],
        ];
    }
}
