<x-filament-panels::page>
    @php
        $latestBackup = $this->latestBackup();
        $lastSuccessfulBackup = $this->lastSuccessfulBackup();
        $backupRuns = $this->backupRuns();
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Database Backup
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Creates a compressed database backup locally, copies it to /mnt/databackup, and uploads it to Google Drive when enabled.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="cleanupBackups"
                    wire:loading.attr="disabled"
                    wire:target="cleanupBackups"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:opacity-70 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <span wire:loading.remove wire:target="cleanupBackups">
                        Cleanup Old Backups
                    </span>

                    <span wire:loading wire:target="cleanupBackups">
                        Cleaning...
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="runBackup"
                    wire:loading.attr="disabled"
                    wire:target="runBackup"
                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="runBackup">
                        Backup Now
                    </span>

                    <span wire:loading wire:target="runBackup">
                        Backing up...
                    </span>
                </button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Last Successful
                </div>

                <div class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $lastSuccessfulBackup?->finished_at?->timezone(config('app.timezone'))->format('M d, Y h:i A') ?? 'No successful backup yet' }}
                </div>

                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $lastSuccessfulBackup?->filename ?? '—' }}
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Latest Status
                </div>

                <div class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">
                    {{ strtoupper($latestBackup?->status ?? 'none') }}
                </div>

                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    External: {{ $latestBackup?->external_status ?? '—' }}
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Google Drive
                </div>

                <div class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">
                    {{ strtoupper($latestBackup?->google_drive_status ?? 'disabled') }}
                </div>

                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Uploaded: {{ $latestBackup?->google_drive_uploaded_at?->timezone(config('app.timezone'))->format('M d, Y h:i A') ?? '—' }}
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Completed
                </div>

                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                    {{ $this->completedBackups() }}
                </div>

                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Total backups: {{ $this->totalBackups() }}
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Drive Uploaded / Failed
                </div>

                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                    {{ $this->googleDriveUploadedBackups() }} / {{ $this->googleDriveFailedBackups() }}
                </div>

                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Google Drive backup status.
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Backup History
                </h2>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Showing latest 30 backup runs.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1500px] table-fixed divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <colgroup>
                        <col style="width: 140px;">
                        <col style="width: 280px;">
                        <col style="width: 130px;">
                        <col style="width: 330px;">
                        <col style="width: 360px;">
                        <col style="width: 260px;">
                    </colgroup>
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">File</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">External</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Google Drive</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Error</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($backupRuns as $backup)
                            @php
                                $statusClass = match ($backup->status) {
                                    'completed' => 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-300',
                                    'failed' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                                    'running' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-300',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };

                                $externalClass = match ($backup->external_status) {
                                    'copied' => 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-300',
                                    'failed' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                                    'skipped' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    default => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-300',
                                };

                                $googleDriveClass = match ($backup->google_drive_status) {
                                    'uploaded' => 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-300',
                                    'failed' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                                    'disabled' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    default => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-300',
                                };
                            @endphp

                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200" style="width: 140px; min-width: 140px; max-width: 140px; white-space: nowrap;"
                                >
                                    @php
                                        $backupStartedAt = $backup->started_at?->timezone(config('app.timezone'));
                                    @endphp

                                    @if ($backupStartedAt)
                                        <div
                                            class="font-semibold"
                                            style="white-space: nowrap;"
                                        >
                                            {{ $backupStartedAt->format('M d, Y') }}
                                        </div>

                                        <div
                                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                            style="white-space: nowrap;"
                                        >
                                            {{ $backupStartedAt->format('h:i A') }}
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-4 py-3" style="width: 280px; min-width: 280px; max-width: 280px;">
                                    @php
                                        $filenameParts = explode('-', $backup->filename);
                                        $filenameSecondLine = array_pop($filenameParts);
                                        $filenameFirstLine = implode('-', $filenameParts);
                                    @endphp

                                    <div
                                        class="font-medium leading-5 text-gray-950 dark:text-white"
                                        title="{{ $backup->filename }}"
                                    >
                                        <div style="white-space: nowrap;">
                                            {{ $filenameFirstLine }}
                                        </div>

                                        <div style="white-space: nowrap;">
                                            {{ $filenameSecondLine }}
                                        </div>
                                    </div>

                                    <div class="mt-1 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                        {{ $this->formatBytes($backup->local_size_bytes) }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ strtoupper($backup->status) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 align-top" style="width: 380px; min-width: 380px; max-width: 380px;">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $externalClass }}">
                                        {{ strtoupper($backup->external_status ?? 'pending') }}
                                    </span>

                                    @php
                                        $externalPath = trim((string) $backup->external_path);
                                        $externalDirectory = $externalPath !== '' ? dirname($externalPath) : '';
                                        $externalFilename = $externalPath !== '' ? basename($externalPath) : '';

                                        $externalDirectoryParts = $externalDirectory !== ''
                                            ? array_values(array_filter(explode('/', $externalDirectory)))
                                            : [];

                                        $externalRootLine = count($externalDirectoryParts) >= 2
                                            ? '/' . $externalDirectoryParts[0] . '/' . $externalDirectoryParts[1]
                                            : $externalDirectory;

                                        $externalFolderLine = $externalDirectoryParts[2] ?? '';
                                    @endphp

                                    <div
                                        class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400"
                                        title="{{ $backup->external_path ?? '—' }}"
                                    >
                                        @if ($externalPath !== '')
                                            <div style="white-space: nowrap;">
                                                {{ $externalRootLine }}
                                            </div>

                                            <div style="white-space: nowrap;">
                                                {{ $externalFolderLine }}
                                            </div>

                                            <div style="white-space: nowrap;">
                                                {{ $externalFilename }}
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top" style="width: 330px; min-width: 330px; max-width: 330px;">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $googleDriveClass }}">
                                        {{ strtoupper($backup->google_drive_status ?? 'disabled') }}
                                    </span>

                                    <div class="mt-2 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                        Uploaded:
                                        {{ $backup->google_drive_uploaded_at?->timezone(config('app.timezone'))->format('M d, Y h:i A') ?? '—' }}
                                    </div>

                                    <div
                                        class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400"
                                        title="{{ $backup->google_drive_path ?? '—' }}"
                                    >
                                        {{ $backup->google_drive_path ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-xs text-red-600 dark:text-red-300">
                                    <div class="max-w-[420px] whitespace-pre-wrap break-words">
                                        {{ $backup->error_message ?: '—' }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No backup runs yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
