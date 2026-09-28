<?php

namespace Tests\Feature;

use App\Http\Controllers\CampusContactController;
use App\Http\Controllers\GospelContactController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class ContactCrossDatabaseDuplicateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('campus_contacts');
        Schema::dropIfExists('gospel_contacts');
        Schema::dropIfExists('persons');

        Schema::create(
            'persons',
            function (Blueprint $table): void {
                $table->id();
                $table->string('firstname');
                $table->string('middlename')->nullable();
                $table->string('lastname');
                $table->string('suffix')->nullable();
                $table->string('sex')->nullable();
                $table->string('locality')->nullable();
                $table->timestamps();
            }
        );

        Schema::create(
            'gospel_contacts',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'person_id'
                )->nullable();

                $table->string('firstname')
                    ->nullable();

                $table->string('lastname')
                    ->nullable();

                $table->string('sex')
                    ->nullable();

                $table->string('locality')
                    ->nullable();

                $table->string('contact_place')
                    ->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'campus_contacts',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'person_id'
                )->nullable();

                $table->string('firstname')
                    ->nullable();

                $table->string('lastname')
                    ->nullable();

                $table->string('sex')
                    ->nullable();

                $table->string('locality')
                    ->nullable();

                $table->string('school_campus')
                    ->nullable();

                $table->string('course_strand')
                    ->nullable();

                $table->string('grade_level')
                    ->nullable();

                $table->timestamps();
            }
        );
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('campus_contacts');
        Schema::dropIfExists('gospel_contacts');
        Schema::dropIfExists('persons');

        parent::tearDown();
    }

    public function test_gospel_creation_detects_all_three_sources(): void
    {
        $this->insertSameNameAcrossAllSources();

        $matches = $this->invokePrivate(
            new GospelContactController(),
            'possibleGospelContactMatches',
            [
                'firstname' => 'Juan',
                'lastname' => 'Santos',
            ]
        );

        $this->assertSame(
            [
                'People Database',
                'Gospel Contact',
                'Campus Contact',
            ],
            $matches
                ->pluck('source')
                ->values()
                ->all()
        );
    }

    public function test_campus_creation_detects_all_three_sources(): void
    {
        $this->insertSameNameAcrossAllSources();

        $matches = $this->invokePrivate(
            new CampusContactController(),
            'possibleCampusContactMatches',
            [
                'firstname' => 'Juan',
                'lastname' => 'Santos',
            ]
        );

        $this->assertSame(
            [
                'People Database',
                'Campus Contact',
                'Gospel Contact',
            ],
            $matches
                ->pluck('source')
                ->values()
                ->all()
        );
    }

    public function test_linked_contact_mirrors_are_not_listed_twice(): void
    {
        DB::table('persons')->insert([
            'id' => 1,
            'firstname' => 'Maria',
            'lastname' => 'Reyes',
        ]);

        DB::table('gospel_contacts')->insert([
            'person_id' => 1,
            'firstname' => 'Maria',
            'lastname' => 'Reyes',
        ]);

        DB::table('campus_contacts')->insert([
            'person_id' => 1,
            'firstname' => 'Maria',
            'lastname' => 'Reyes',
        ]);

        $gospelMatches = $this->invokePrivate(
            new GospelContactController(),
            'possibleGospelContactMatches',
            [
                'firstname' => 'Maria',
                'lastname' => 'Reyes',
            ]
        );

        $campusMatches = $this->invokePrivate(
            new CampusContactController(),
            'possibleCampusContactMatches',
            [
                'firstname' => 'Maria',
                'lastname' => 'Reyes',
            ]
        );

        $this->assertSame(
            ['People Database'],
            $gospelMatches
                ->pluck('source')
                ->values()
                ->all()
        );

        $this->assertSame(
            ['People Database'],
            $campusMatches
                ->pluck('source')
                ->values()
                ->all()
        );
    }

    public function test_first_name_alone_is_not_a_duplicate(): void
    {
        DB::table('persons')->insert([
            'firstname' => 'Pedro',
            'lastname' => 'Cruz',
        ]);

        $gospelMatches = $this->invokePrivate(
            new GospelContactController(),
            'possibleGospelContactMatches',
            [
                'firstname' => 'Pedro',
                'lastname' => 'Santos',
            ]
        );

        $campusMatches = $this->invokePrivate(
            new CampusContactController(),
            'possibleCampusContactMatches',
            [
                'firstname' => 'Pedro',
                'lastname' => 'Santos',
            ]
        );

        $this->assertTrue(
            $gospelMatches->isEmpty()
        );

        $this->assertTrue(
            $campusMatches->isEmpty()
        );
    }

    private function insertSameNameAcrossAllSources(): void
    {
        DB::table('persons')->insert([
            'firstname' => 'Juan',
            'lastname' => 'Santos',
            'locality' => 'Lucena',
        ]);

        DB::table('gospel_contacts')->insert([
            'firstname' => 'Juan',
            'lastname' => 'Santos',
            'locality' => 'Lucena',
            'contact_place' => 'Home',
        ]);

        DB::table('campus_contacts')->insert([
            'firstname' => 'Juan',
            'lastname' => 'Santos',
            'locality' => 'Lucena',
            'school_campus' => 'Sample School',
        ]);
    }

    private function invokePrivate(
        object $controller,
        string $method,
        array $data
    ) {
        $reflection =
            new ReflectionMethod(
                $controller,
                $method
            );

        return $reflection->invoke(
            $controller,
            $data
        );
    }
}
