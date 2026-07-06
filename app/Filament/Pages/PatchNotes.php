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
                [$hash, $dateTime, $subject] = array_pad(explode('|', $line, 3), 3, '');

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
                    'type' => $parsed['type'],
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

        if (preg_match('/^(Phase\s+[A-Za-z0-9.]+)\s+-\s+(.+)$/', $subject, $matches)) {
            $tag = $matches[1];
            $detail = $matches[2];
        } elseif (preg_match('/^(UI Cleanup\d*\s+v[0-9A-Za-z.]+)\s+-\s+(.+)$/', $subject, $matches)) {
            $tag = $matches[1];
            $detail = $matches[2];
        } elseif (preg_match('/^(Production|Safety|Feature UI|Logic|GitHub)(?:\s+Phase\s+[A-Za-z0-9.]+)?\s+-\s+(.+)$/', $subject, $matches)) {
            $tag = $matches[1];
            $detail = $matches[2];
        }

        return [
            'tag' => $tag,
            'type' => $this->changeType($detail),
            'title' => $this->humanTitle($detail),
            'description' => $this->plainDescription($detail),
        ];
    }

    private function changeType(string $detail): string
    {
        $detail = Str::lower($detail);

        return match (true) {
            str_starts_with($detail, 'fix') => 'Fixed',
            str_starts_with($detail, 'add') => 'Added',
            str_starts_with($detail, 'create') => 'Added',
            str_starts_with($detail, 'remove') => 'Removed',
            str_starts_with($detail, 'delete') => 'Removed',
            str_starts_with($detail, 'hide') => 'Improved',
            str_starts_with($detail, 'clean') => 'Improved',
            str_starts_with($detail, 'polish') => 'Improved',
            str_starts_with($detail, 'improve') => 'Improved',
            str_starts_with($detail, 'rename') => 'Renamed',
            str_starts_with($detail, 'restore') => 'Restored',
            str_starts_with($detail, 'separate') => 'Organized',
            str_starts_with($detail, 'allow') => 'Updated',
            str_starts_with($detail, 'store') => 'Updated',
            default => 'Updated',
        };
    }

    private function humanTitle(string $detail): string
    {
        $detail = trim($detail);
        $detail = preg_replace('/^(Add|Fix|Clean|Polish|Improve|Improved|Remove|Delete|Hide|Rename|Restore|Separate|Allow|Store)\s+/i', '', $detail);

        return Str::headline($detail);
    }

    private function plainDescription(string $detail): string
    {
        $detail = trim($detail);

        $replacements = [
            'Add ' => 'Added ',
            'Fix ' => 'Fixed ',
            'Clean ' => 'Improved ',
            'Polish ' => 'Improved ',
            'Improve ' => 'Improved ',
            'Remove ' => 'Removed ',
            'Delete ' => 'Deleted ',
            'Hide ' => 'Hid ',
            'Rename ' => 'Renamed ',
            'Restore ' => 'Restored ',
            'Separate ' => 'Separated ',
            'Allow ' => 'Allowed ',
            'Store ' => 'Updated storage for ',
        ];

        foreach ($replacements as $from => $to) {
            if (str_starts_with($detail, $from)) {
                $detail = $to . Str::after($detail, $from);

                break;
            }
        }

        return Str::ucfirst($detail) . '.';
    }
}
