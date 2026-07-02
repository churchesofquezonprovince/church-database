<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ActivityLogger
{
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $oldValues = [],
        array $newValues = [],
    ): void {
        try {
            ActivityLog::query()->create([
                'user_id' => Auth::id(),
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_name' => $subject ? self::subjectName($subject) : null,
                'description' => $description,
                'old_values' => self::cleanValues($oldValues),
                'new_values' => self::cleanValues($newValues),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Never break the app because logging failed.
        }
    }

    private static function subjectName(Model $subject): string
    {
        if (isset($subject->display_name)) {
            return (string) $subject->display_name;
        }

        if (isset($subject->household_name)) {
            return (string) $subject->household_name;
        }

        if (isset($subject->name)) {
            return (string) $subject->name;
        }

        return class_basename($subject) . ' #' . $subject->getKey();
    }

    private static function cleanValues(array $values): array
    {
        return Arr::except($values, [
            'password',
            'remember_token',
            'email_verified_at',
            'created_at',
            'updated_at',
        ]);
    }
}
