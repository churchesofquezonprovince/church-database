<?php

namespace Tests\Feature;

use App\Http\Controllers\PeopleImportController;
use ReflectionMethod;
use Tests\TestCase;

class PeopleImportPartialBaptismDateTest extends TestCase
{
    public function test_legacy_year_only_still_works(): void
    {
        $parts = $this->resolve([
            'baptism_date' => '2024',
        ]);

        $this->assertSame(
            [
                'year' => 2024,
                'month' => null,
                'day' => null,
            ],
            $parts
        );
    }

    public function test_legacy_year_and_month_still_work(): void
    {
        $parts = $this->resolve([
            'baptism_date' => '2024-03',
        ]);

        $this->assertSame(
            [
                'year' => 2024,
                'month' => 3,
                'day' => null,
            ],
            $parts
        );
    }

    public function test_month_only_can_be_imported(): void
    {
        $parts = $this->resolve([
            'baptism_month' => '3',
        ]);

        $this->assertSame(
            [
                'year' => null,
                'month' => 3,
                'day' => null,
            ],
            $parts
        );
    }

    public function test_day_only_can_be_imported(): void
    {
        $parts = $this->resolve([
            'baptism_day' => '15',
        ]);

        $this->assertSame(
            [
                'year' => null,
                'month' => null,
                'day' => 15,
            ],
            $parts
        );
    }

    public function test_month_and_day_can_be_imported_without_year(): void
    {
        $parts = $this->resolve([
            'baptism_month' => '3',
            'baptism_day' => '15',
        ]);

        $this->assertSame(
            [
                'year' => null,
                'month' => 3,
                'day' => 15,
            ],
            $parts
        );
    }

    public function test_complete_independent_date_can_be_imported(): void
    {
        $parts = $this->resolve([
            'baptism_year' => '2024',
            'baptism_month' => '3',
            'baptism_day' => '15',
        ]);

        $this->assertSame(
            [
                'year' => 2024,
                'month' => 3,
                'day' => 15,
            ],
            $parts
        );
    }

    public function test_explicit_component_can_complete_legacy_partial_date(): void
    {
        $parts = $this->resolve([
            'baptism_date' => '2024-03',
            'baptism_day' => '15',
        ]);

        $this->assertSame(
            [
                'year' => 2024,
                'month' => 3,
                'day' => 15,
            ],
            $parts
        );
    }

    private function resolve(
        array $row
    ): array {
        $controller =
            new PeopleImportController();

        $method =
            new ReflectionMethod(
                $controller,
                'resolveBaptismParts'
            );

        return $method->invoke(
            $controller,
            $row
        );
    }
}
