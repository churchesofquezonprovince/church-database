<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PatchNotes extends Page
{
    protected string $view = 'filament.pages.patch-notes';

    public function getTitle(): string
    {
        return 'Patch Notes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Patch Notes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function patchNotes(): Collection
    {
        $output = $this->gitLogOutput();

        if (blank($output)) {
            return collect();
        }

        return collect(explode("\n", trim($output)))
            ->filter()
            ->map(function (string $line): array {
                [$hash, $dateTime, $subject] = array_pad(
                    explode('|', $line, 3),
                    3,
                    ''
                );

                $dateTime = trim($dateTime);
                $date = Str::before($dateTime, ' ');
                $time = Str::after($dateTime, ' ');

                $parsed = $this->parseSubject($subject);

                return [
                    'hash' => $hash,
                    'date_time' => $dateTime,
                    'date' => $date,
                    'time' => $time,

                    'tag' => $parsed['tag'],

                    /*
                     * "type" remains available for compatibility
                     * with the old Blade.
                     */
                    'type' => $parsed['type'],

                    /*
                     * A commit can now contain multiple types:
                     *
                     * Fixed X and Repair Y
                     * => ['Fixed', 'Repaired']
                     */
                    'types' => $parsed['types'],

                    'title' => $parsed['title'],
                    'description' => $parsed['description'],
                    'original' => $subject,
                ];
            })
            ->values();
    }

    public function groupedPatchNotes(): Collection
    {
        return $this->patchNotes()
            ->groupBy('date');
    }

    public function gitAvailable(): bool
    {
        return $this->patchNotes()->isNotEmpty();
    }

    private function gitLogOutput(): string
    {
        $command = 'git -C '
            . escapeshellarg(base_path())
            . " log --date=format:'%Y-%m-%d %H:%M' --pretty=format:"
            . escapeshellarg('%h|%cd|%s')
            . ' 2>/dev/null';

        $lines = [];
        $exitCode = 0;

        exec($command, $lines, $exitCode);

        if ($exitCode !== 0) {
            return '';
        }

        return implode("\n", $lines);
    }

    private function parseSubject(string $subject): array
    {
        $tag = 'General';
        $detail = trim($subject);

        /*
         * Phase 25 - Something
         */
        if (
            preg_match(
                '/^(Phase\s+[A-Za-z0-9.]+)\s+-\s+(.+)$/',
                $subject,
                $matches
            )
        ) {
            $tag = $matches[1];
            $detail = $matches[2];
        }

        /*
         * UI Cleanup2 v1 - Something
         */
        elseif (
            preg_match(
                '/^(UI Cleanup\d*\s+v[0-9A-Za-z.]+)\s+-\s+(.+)$/',
                $subject,
                $matches
            )
        ) {
            $tag = $matches[1];
            $detail = $matches[2];
        }

        /*
         * Production - Something
         * Safety - Something
         * Feature UI - Something
         * Logic - Something
         * GitHub - Something
         */
        elseif (
            preg_match(
                '/^(Production|Safety|Feature UI|Logic|GitHub)'
                . '(?:\s+Phase\s+[A-Za-z0-9.]+)?'
                . '\s+-\s+(.+)$/',
                $subject,
                $matches
            )
        ) {
            $tag = $matches[1];
            $detail = $matches[2];
        }

        $types = $this->changeTypes($detail);

        return [
            'tag' => $tag,

            /*
             * First detected type for backward compatibility.
             */
            'type' => $types[0] ?? 'Updated',

            /*
             * All detected change types.
             */
            'types' => $types,

            'title' => $this->humanTitle($detail),
            'description' => $this->plainDescription($detail),
        ];
    }

    private function changeTypes(string $detail): array
    {
        /*
         * Search the ENTIRE commit message.
         *
         * A commit can therefore produce more than one badge.
         *
         * Example:
         *
         * "Fixed Add Lesson blade and Repair Google sync"
         *
         * becomes:
         *
         * Fixed
         * Added
         * Repaired
         *
         * Types are kept in the same order that the action
         * words appear in the commit message.
         */
        $patterns = [
            'Added' => '/\b(?:add|added|create|created)\b/i',

            'Fixed' => '/\b(?:fix|fixed)\b/i',

            'Repaired' => '/\b(?:repair|repaired)\b/i',

            'Removed' => '/\b(?:remove|removed|delete|deleted)\b/i',

            'Improved' => '/\b(?:hide|hid|clean|cleaned|polish|polished|improve|improved)\b/i',

            'Renamed' => '/\b(?:rename|renamed)\b/i',

            'Restored' => '/\b(?:restore|restored)\b/i',

            'Organized' => '/\b(?:separate|separated|organize|organized)\b/i',

            'Updated' => '/\b(?:update|updated|allow|allowed|store|stored)\b/i',
        ];

        $found = [];

        foreach ($patterns as $type => $pattern) {
            if (
                preg_match(
                    $pattern,
                    $detail,
                    $matches,
                    PREG_OFFSET_CAPTURE
                )
            ) {
                $found[] = [
                    'type' => $type,
                    'position' => $matches[0][1],
                ];
            }
        }

        /*
         * No recognizable action keyword.
         */
        if ($found === []) {
            return ['Updated'];
        }

        /*
         * Preserve the order in which the keywords
         * appeared in the commit message.
         */
        usort(
            $found,
            fn (array $a, array $b): int =>
                $a['position'] <=> $b['position']
        );

        /*
         * One badge per category.
         *
         * Example:
         *
         * "Repair X and Repair Y"
         *
         * displays one Repaired badge rather than two.
         */
        return collect($found)
            ->pluck('type')
            ->unique()
            ->values()
            ->all();
    }

    private function humanTitle(string $detail): string
    {
        $detail = trim($detail);

        /*
         * Remove an action word only when it is at the
         * beginning of the message.
         *
         * Action words later in the message are preserved.
         */
        $detail = preg_replace(
            '/^(?:'
            . 'Add|Added|'
            . 'Create|Created|'
            . 'Fix|Fixed|'
            . 'Repair|Repaired|'
            . 'Clean|Cleaned|'
            . 'Polish|Polished|'
            . 'Improve|Improved|'
            . 'Remove|Removed|'
            . 'Delete|Deleted|'
            . 'Hide|Hid|'
            . 'Rename|Renamed|'
            . 'Restore|Restored|'
            . 'Separate|Separated|'
            . 'Organize|Organized|'
            . 'Allow|Allowed|'
            . 'Store|Stored|'
            . 'Update|Updated'
            . ')\s+/i',
            '',
            $detail
        );

        return Str::headline($detail);
    }

    private function plainDescription(string $detail): string
    {
        $detail = trim($detail);

        /*
         * Turn the first action into past tense for a
         * friendlier non-technical description.
         */
        $replacements = [
            'Add ' => 'Added ',
            'Added ' => 'Added ',

            'Create ' => 'Created ',
            'Created ' => 'Created ',

            'Fix ' => 'Fixed ',
            'Fixed ' => 'Fixed ',

            'Repair ' => 'Repaired ',
            'Repaired ' => 'Repaired ',

            'Clean ' => 'Improved ',
            'Cleaned ' => 'Improved ',

            'Polish ' => 'Improved ',
            'Polished ' => 'Improved ',

            'Improve ' => 'Improved ',
            'Improved ' => 'Improved ',

            'Remove ' => 'Removed ',
            'Removed ' => 'Removed ',

            'Delete ' => 'Deleted ',
            'Deleted ' => 'Deleted ',

            'Hide ' => 'Hid ',
            'Hid ' => 'Hid ',

            'Rename ' => 'Renamed ',
            'Renamed ' => 'Renamed ',

            'Restore ' => 'Restored ',
            'Restored ' => 'Restored ',

            'Separate ' => 'Separated ',
            'Separated ' => 'Separated ',

            'Organize ' => 'Organized ',
            'Organized ' => 'Organized ',

            'Allow ' => 'Allowed ',
            'Allowed ' => 'Allowed ',

            'Store ' => 'Updated storage for ',
            'Stored ' => 'Updated storage for ',

            'Update ' => 'Updated ',
            'Updated ' => 'Updated ',
        ];

        foreach ($replacements as $from => $to) {
            if (str_starts_with($detail, $from)) {
                $detail = $to . Str::after(
                    $detail,
                    $from
                );

                break;
            }
        }

        /*
         * Avoid accidentally ending with ".."
         */
        $detail = rtrim($detail, ". \t\n\r\0\x0B");

        return Str::ucfirst($detail) . '.';
    }

    public function getTotalCodingTime(): string
    {
        $output = $this->gitLogOutput();

        if (blank($output)) {
            return '1 hr Coded';
        }

        $lines = array_filter(
            explode("\n", trim($output))
        );

        $totalUpdates = count($lines);

        if ($totalUpdates === 0) {
            return '1 hr Coded';
        }

        /*
         * Approximate coding time based on the number
         * of Git commits/updates.
         */
        $totalHours = round(
            $totalUpdates * 4.15,
            1
        );

        return $totalHours . ' hrs Coded';
    }
}