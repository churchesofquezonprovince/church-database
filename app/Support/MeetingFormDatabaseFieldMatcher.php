<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

final class MeetingFormDatabaseFieldMatcher
{
    public function __construct(
        private readonly MeetingFormRespondentResolver $resolver
    ) {
    }

    public function matches(
        array $identity,
        string $field,
        mixed $submittedValue
    ): bool {
        if (
            ! MeetingFormDatabaseFieldRegistry
                ::isAutofillMatchEligible(
                    $field
                )
        ) {
            return false;
        }

        $resolved =
            $this->resolver->fieldValue(
                $identity,
                $field
            );

        if (
            ! ($resolved[
                'has_existing_value'
            ] ?? false)
        ) {
            /*
             * A newly supplied value cannot prove a match
             * when no existing value is available.
             */
            return false;
        }

        $definition =
            MeetingFormDatabaseFieldRegistry::definition(
                $field
            );

        $input =
            $definition['input']
            ?? 'text';

        $submitted =
            $this->normalize(
                $submittedValue,
                $input
            );

        $existing =
            $this->normalize(
                $resolved['value']
                    ?? null,
                $input
            );

        if (
            $submitted === null
            || $existing === null
        ) {
            return false;
        }

        return hash_equals(
            $existing,
            $submitted
        );
    }

    public function matchingFields(
        array $identity,
        array $submittedFields
    ): array {
        $matches = [];

        foreach (
            $submittedFields
            as $field => $value
        ) {
            $field =
                (string) $field;

            if (
                $this->matches(
                    $identity,
                    $field,
                    $value
                )
            ) {
                $matches[] =
                    $field;
            }
        }

        return array_values(
            array_unique(
                $matches
            )
        );
    }

    private function normalize(
        mixed $value,
        string $input
    ): ?string {
        if (
            $value === null
            || is_array($value)
            || is_object($value)
        ) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return null;
        }

        return match ($input) {
            'date' =>
                $this->normalizeDate(
                    $value
                ),

            'locality',
            'school' =>
                is_numeric($value)
                    ? (string) (int) $value
                    : null,

            'email' =>
                mb_strtolower(
                    $value
                ),

            'tel' =>
                $this->normalizeTelephone(
                    $value
                ),

            default =>
                $this->normalizeText(
                    $value
                ),
        };
    }

    private function normalizeDate(
        string $value
    ): ?string {
        try {
            return CarbonImmutable::parse(
                $value
            )->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeTelephone(
        string $value
    ): ?string {
        $digits =
            preg_replace(
                '/\\D+/',
                '',
                $value
            );

        if (
            ! is_string($digits)
            || $digits === ''
        ) {
            return null;
        }

        return $digits;
    }

    private function normalizeText(
        string $value
    ): ?string {
        $value =
            preg_replace(
                '/\\s+/u',
                ' ',
                trim($value)
            );

        if (
            ! is_string($value)
            || $value === ''
        ) {
            return null;
        }

        return mb_strtolower(
            $value
        );
    }
}
