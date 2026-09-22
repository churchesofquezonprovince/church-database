<?php

namespace App\Filament\Pages;

use App\Models\DeveloperSetting;
use App\Models\NavigationSearchAlias;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Process;
use Throwable;
use Illuminate\Support\Facades\Artisan;
class DeveloperOptions extends Page
{
    use \App\Filament\Concerns\ManagesProblemReports;


    // Developer Options: System Information
    public string $appVersion = 'Not configured';

    public string $currentPhase = 'Not configured';

    public string $gitCommitHash = 'Unavailable';

    public string $laravelVersion = '';

    public string $phpVersion = '';

    protected string $view = 'filament.pages.developer-options';

    protected static ?string $slug = 'developer-options';

    public string $phrase = '';

    public string $targetType = 'item';

    public string $targetKey = '';

    public bool $isActive = true;

    /*
     * Number of distinct existing Database Fields that must
     * silently match before remaining configured Database
     * Fields may be auto-filled on a public meeting form.
     *
     * 0 = never reveal / auto-fill stored values.
     */
    public int $databaseFieldMatchesBeforeAutofill = 2;

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
        return 50;
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

        $this->loadSystemInformation();

        $this->serviceMeetingFolder =
            \App\Models\DriveMeetingDocument::serviceMeetingFolderUrl();

        $this->databaseFieldMatchesBeforeAutofill =
            max(
                0,
                min(
                    5,
                    DeveloperSetting::integer(
                        DeveloperSetting::KEY_MEETING_FORM_AUTOFILL_MATCHES,
                        2
                    )
                )
            );
    }


    public string $serviceMeetingFolder = '';


    public function testServiceMeetingGoogleAccount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        try {
            \App\Services\ServiceMeetingGoogleAccount::testFolder();

            Notification::make()
                ->title('Folder access verified')
                ->body('The active service account can read the saved Google Drive folder.')
                ->success()
                ->send();
        } catch (\Throwable) {
            Notification::make()
                ->title('Folder access could not be verified')
                ->body('Check the credentials, saved folder, Viewer sharing permission, and Drive API availability.')
                ->danger()
                ->send();
        }
    }

    public function saveServiceMeetingFolder(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->validate([
            'serviceMeetingFolder' => ['required', 'string', 'max:2000'],
        ]);

        $input = trim($this->serviceMeetingFolder);
        $folder = $input;

        if (str_contains($input, '://')) {
            $parts = parse_url($input);

            if (
                ! is_array($parts)
                || strtolower($parts['scheme'] ?? '') !== 'https'
                || strtolower($parts['host'] ?? '') !== 'drive.google.com'
                || isset($parts['user'])
                || isset($parts['pass'])
                || isset($parts['port'])
                || ! preg_match(
                    '~^/drive/(?:u/\d+/)?folders/([A-Za-z0-9_-]+)/?$~',
                    $parts['path'] ?? '',
                    $matches
                )
            ) {
                throw ValidationException::withMessages([
                    'serviceMeetingFolder' => 'Enter a Google Drive folder URL or folder ID.',
                ]);
            }

            $folder = $matches[1];
        }

        if (! preg_match('/^[A-Za-z0-9_-]{10,200}$/', $folder)) {
            throw ValidationException::withMessages([
                'serviceMeetingFolder' => 'Enter a valid Google Drive folder ID.',
            ]);
        }

        $oldKey = \App\Models\DriveMeetingDocument::documentCacheKey();

        DeveloperSetting::putValue('service_meeting_drive_folder', $folder);

        \Illuminate\Support\Facades\Cache::forget($oldKey);
        \Illuminate\Support\Facades\Cache::forget(
            \App\Models\DriveMeetingDocument::documentCacheKey()
        );
        \Illuminate\Support\Facades\Cache::forget('service_meeting_docs');

        $this->serviceMeetingFolder =
            \App\Models\DriveMeetingDocument::serviceMeetingFolderUrl();

        Notification::make()
            ->title('Service Meeting Minutes folder saved')
            ->body('The document list will reload from this folder when opened. Folder access has not been tested.')
            ->success()
            ->send();
    }

    public function refreshServiceMeetingDocuments(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        \Illuminate\Support\Facades\Cache::forget(
            \App\Models\DriveMeetingDocument::documentCacheKey()
        );
        \Illuminate\Support\Facades\Cache::forget('service_meeting_docs');

        Notification::make()
            ->title('Document list cache cleared')
            ->body('Open or reload Service Meeting Minutes to fetch the latest documents.')
            ->success()
            ->send();
    }

    public function saveMeetingFormAutofillSettings(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $data = $this->validate([
            'databaseFieldMatchesBeforeAutofill' => [
                'required',
                'integer',
                'min:0',
                'max:5',
            ],
        ]);

        DeveloperSetting::putValue(
            DeveloperSetting::KEY_MEETING_FORM_AUTOFILL_MATCHES,
            (int) $data[
                'databaseFieldMatchesBeforeAutofill'
            ]
        );

        Notification::make()
            ->title('Meeting form autofill settings saved')
            ->body(
                'The hidden Database Field match threshold '
                . 'has been updated.'
            )
            ->success()
            ->send();
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

    protected function loadSystemInformation(): void
    {
        $this->appVersion =
            config(
                'app.version',
                'Not configured'
            );

        $this->laravelVersion =
            app()->version();

        $this->phpVersion =
            PHP_VERSION;

        try {
            $commitResult =
                Process::path(
                    base_path()
                )->run(
                    'git rev-parse --short HEAD'
                );

            if ($commitResult->successful()) {
                $hash =
                    trim(
                        $commitResult->output()
                    );

                if ($hash !== '') {
                    $this->gitCommitHash =
                        $hash;
                }
            }

            /*
             * System Information should reflect the latest
             * committed development phase automatically rather
             * than relying on a manually maintained APP_PHASE.
             */
            $phaseResult =
                Process::path(
                    base_path()
                )->run([
                    'git',
                    'log',
                    '-1',
                    '--format=%s',
                    '--grep=^Phase ',
                ]);

            if ($phaseResult->successful()) {
                $subject =
                    trim(
                        $phaseResult->output()
                    );

                if (
                    preg_match(
                        '/^(Phase\\s+[^\\s]+)/',
                        $subject,
                        $matches
                    ) === 1
                ) {
                    $this->currentPhase =
                        $matches[1];
                }
            }
        } catch (Throwable $e) {
            $this->gitCommitHash =
                'Unavailable';

            $this->currentPhase =
                'Unavailable';
        }
    }


    // Developer Options: Cache & Maintenance

    public function clearApplicationCache(): void
    {
        $this->runMaintenanceCommand(
            'cache:clear',
            'Application cache cleared successfully.'
        );
    }

    public function clearConfigCache(): void
    {
        $this->runMaintenanceCommand(
            'config:clear',
            'Configuration cache cleared successfully.'
        );
    }

    public function clearViewCache(): void
    {
        $this->runMaintenanceCommand(
            'view:clear',
            'Compiled view cache cleared successfully.'
        );
    }

    public function clearRouteCache(): void
    {
        $this->runMaintenanceCommand(
            'route:clear',
            'Route cache cleared successfully.'
        );
    }

    public function rebuildCaches(): void
    {
        try {
            Artisan::call('optimize:clear');
            Artisan::call('optimize');

            Notification::make()
                ->title('Caches rebuilt')
                ->body('Application optimization caches were rebuilt successfully.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Cache rebuild failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function runMaintenanceCommand(
        string $command,
        string $successMessage
    ): void {
        try {
            Artisan::call($command);

            Notification::make()
                ->title('Maintenance completed')
                ->body($successMessage)
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Maintenance command failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

}
