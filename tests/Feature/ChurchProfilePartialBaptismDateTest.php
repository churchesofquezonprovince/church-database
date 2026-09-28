<?php

namespace Tests\Feature;

use App\Models\ChurchProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChurchProfilePartialBaptismDateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('church_profiles');

        Schema::create(
            'church_profiles',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger('person_id')
                    ->nullable();

                $table->string('category')
                    ->nullable();

                $table->string('status')
                    ->nullable();

                $table->date('baptism_date')
                    ->nullable();

                $table->unsignedSmallInteger(
                    'baptism_year'
                )->nullable();

                $table->unsignedTinyInteger(
                    'baptism_month'
                )->nullable();

                $table->unsignedTinyInteger(
                    'baptism_day'
                )->nullable();

                $table->timestamps();
            }
        );
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('church_profiles');

        parent::tearDown();
    }

    public function test_month_can_be_saved_without_year(): void
    {
        $profile = ChurchProfile::query()->create([
            'baptism_month' => 3,
        ]);

        $profile->refresh();

        $this->assertNull(
            $profile->baptism_year
        );

        $this->assertSame(
            3,
            $profile->baptism_month
        );

        $this->assertNull(
            $profile->baptism_day
        );

        $this->assertNull(
            $profile->baptism_date
        );
    }

    public function test_day_can_be_saved_without_year_or_month(): void
    {
        $profile = ChurchProfile::query()->create([
            'baptism_day' => 15,
        ]);

        $profile->refresh();

        $this->assertNull(
            $profile->baptism_year
        );

        $this->assertNull(
            $profile->baptism_month
        );

        $this->assertSame(
            15,
            $profile->baptism_day
        );

        $this->assertNull(
            $profile->baptism_date
        );
    }

    public function test_month_and_day_can_be_saved_without_year(): void
    {
        $profile = ChurchProfile::query()->create([
            'baptism_month' => 3,
            'baptism_day' => 15,
        ]);

        $profile->refresh();

        $this->assertNull(
            $profile->baptism_year
        );

        $this->assertSame(
            3,
            $profile->baptism_month
        );

        $this->assertSame(
            15,
            $profile->baptism_day
        );

        $this->assertNull(
            $profile->baptism_date
        );
    }

    public function test_year_only_does_not_invent_month_or_day(): void
    {
        $profile = ChurchProfile::query()->create([
            'baptism_year' => 2024,
        ]);

        $profile->refresh();

        $this->assertSame(
            2024,
            $profile->baptism_year
        );

        $this->assertNull(
            $profile->baptism_month
        );

        $this->assertNull(
            $profile->baptism_day
        );

        $this->assertNull(
            $profile->baptism_date
        );
    }

    public function test_year_and_month_do_not_invent_day(): void
    {
        $profile = ChurchProfile::query()->create([
            'baptism_year' => 2024,
            'baptism_month' => 3,
        ]);

        $profile->refresh();

        $this->assertSame(
            2024,
            $profile->baptism_year
        );

        $this->assertSame(
            3,
            $profile->baptism_month
        );

        $this->assertNull(
            $profile->baptism_day
        );

        $this->assertNull(
            $profile->baptism_date
        );
    }

    public function test_complete_baptism_date_updates_legacy_date(): void
    {
        $profile = ChurchProfile::query()->create([
            'baptism_year' => 2024,
            'baptism_month' => 3,
            'baptism_day' => 15,
        ]);

        $profile->refresh();

        $this->assertSame(
            '2024-03-15',
            $profile->baptism_date
                ?->format('Y-m-d')
        );
    }
}
