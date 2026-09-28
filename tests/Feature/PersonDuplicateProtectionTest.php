<?php

namespace Tests\Feature;

use App\Models\Person;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PersonDuplicateProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('church_profiles');
        Schema::dropIfExists('persons');

        Schema::create('persons', function (Blueprint $table): void {
            $table->id();

            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->string('suffix')->nullable();

            $table->string('sex')->nullable();
            $table->string('nickname')->nullable();

            $table->date('birthdate')->nullable();
            $table->string('birthplace')->nullable();

            $table->unsignedBigInteger('household_id')
                ->nullable();

            $table->unsignedBigInteger('spouse_id')
                ->nullable();

            $table->string('locality')->nullable();

            $table->unsignedBigInteger('locality_id')
                ->nullable();

            $table->text('permanent_address')
                ->nullable();

            $table->text('home_address')
                ->nullable();

            $table->string('geocoordinates')
                ->nullable();

            $table->string('email')->nullable();

            $table->string('facebook_account')
                ->nullable();

            $table->string('contact_number')
                ->nullable();

            $table->unsignedBigInteger(
                'emergency_contact_id'
            )->nullable();

            $table->string(
                'emergency_contact_relationship'
            )->nullable();

            $table->string(
                'emergency_contact_number'
            )->nullable();

            $table->timestamps();
        });

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

                $table->timestamps();
            }
        );

        Schema::create(
            'church_profiles',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger('person_id')
                    ->unique();

                $table->string('category')
                    ->nullable();

                $table->string('status')
                    ->nullable();

                $table->date('baptism_date')
                    ->nullable();

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
        Schema::dropIfExists('campus_contacts');
        Schema::dropIfExists('gospel_contacts');
        Schema::dropIfExists('church_profiles');
        Schema::dropIfExists('persons');

        parent::tearDown();
    }

    public function test_exact_person_duplicate_is_blocked_normally(): void
    {
        Person::query()->create([
            'firstname' => 'Juan',
            'lastname' => 'Santos',
            'birthdate' => '2000-01-15',
            'sex' => 'Male',
        ]);

        $this->expectException(
            ValidationException::class
        );

        Person::query()->create([
            'firstname' => 'Juan',
            'lastname' => 'Santos',
            'birthdate' => '2005-08-20',
            'sex' => 'Male',
        ]);
    }

    public function test_exact_duplicate_can_be_created_through_scoped_bypass(): void
    {
        Person::query()->create([
            'firstname' => 'Maria',
            'lastname' => 'Reyes',
            'birthdate' => '1998-06-20',
            'sex' => 'Female',
        ]);

        Person::createAllowingExactDuplicate([
            'firstname' => 'Maria',
            'lastname' => 'Reyes',
            'birthdate' => '2001-11-03',
            'sex' => 'Female',
        ]);

        $this->assertSame(
            2,
            Person::query()
                ->where(
                    'firstname',
                    'Maria'
                )
                ->where(
                    'lastname',
                    'Reyes'
                )
                ->count()
        );
    }

    public function test_intentional_duplicate_can_be_edited_when_identity_is_unchanged(): void
    {
        Person::query()->create([
            'firstname' => 'Ana',
            'lastname' => 'Garcia',
            'birthdate' => '1999-02-14',
            'sex' => 'Female',
        ]);

        $duplicate =
            Person::createAllowingExactDuplicate([
                'firstname' => 'Ana',
                'lastname' => 'Garcia',
                'birthdate' => '1999-02-14',
                'sex' => 'Female',
            ]);

        $duplicate->contact_number =
            '09123456789';

        $duplicate->save();

        $this->assertSame(
            '09123456789',
            $duplicate->fresh()->contact_number
        );
    }

    public function test_editing_identity_into_an_exact_duplicate_is_still_blocked(): void
    {
        Person::query()->create([
            'firstname' => 'Luis',
            'lastname' => 'Mendoza',
            'birthdate' => '1990-05-10',
            'sex' => 'Male',
        ]);

        $person = Person::query()->create([
            'firstname' => 'Luis',
            'lastname' => 'Rivera',
            'birthdate' => '1990-05-10',
            'sex' => 'Male',
        ]);

        $person->lastname = 'Mendoza';

        $this->expectException(
            ValidationException::class
        );

        $person->save();
    }


    public function test_matching_unlinked_gospel_contact_blocks_person_creation(): void
    {
        DB::table('gospel_contacts')->insert([
            'firstname' => 'Paolo',
            'lastname' => 'Dela Cruz',
            'person_id' => null,
        ]);

        $this->expectException(
            ValidationException::class
        );

        Person::query()->create([
            'firstname' => 'Paolo',
            'lastname' => 'Dela Cruz',
            'sex' => 'Male',
        ]);
    }

    public function test_matching_unlinked_campus_contact_blocks_person_creation(): void
    {
        DB::table('campus_contacts')->insert([
            'firstname' => 'Grace',
            'lastname' => 'Villanueva',
            'person_id' => null,
        ]);

        $this->expectException(
            ValidationException::class
        );

        Person::query()->create([
            'firstname' => 'Grace',
            'lastname' => 'Villanueva',
            'sex' => 'Female',
        ]);
    }

    public function test_create_anyway_can_bypass_contact_match(): void
    {
        DB::table('gospel_contacts')->insert([
            'firstname' => 'Carlo',
            'lastname' => 'Ramos',
            'person_id' => null,
        ]);

        Person::createAllowingExactDuplicate([
            'firstname' => 'Carlo',
            'lastname' => 'Ramos',
            'sex' => 'Male',
        ]);

        $this->assertSame(
            1,
            Person::query()
                ->where(
                    'firstname',
                    'Carlo'
                )
                ->where(
                    'lastname',
                    'Ramos'
                )
                ->count()
        );
    }

    public function test_duplicate_guard_is_restored_after_scoped_bypass(): void
    {
        Person::query()->create([
            'firstname' => 'Pedro',
            'lastname' => 'Cruz',
            'birthdate' => '1995-09-10',
            'sex' => 'Male',
        ]);

        Person::createAllowingExactDuplicate([
            'firstname' => 'Pedro',
            'lastname' => 'Cruz',
            'birthdate' => '1995-09-10',
            'sex' => 'Male',
        ]);

        try {
            Person::query()->create([
                'firstname' => 'Pedro',
                'lastname' => 'Cruz',
                'birthdate' => '1995-09-10',
                'sex' => 'Male',
            ]);

            $this->fail(
                'Duplicate validation should have been restored.'
            );
        } catch (ValidationException) {
            $this->assertSame(
                2,
                Person::query()
                    ->where('firstname', 'Pedro')
                    ->where('lastname', 'Cruz')
                    ->whereDate(
                        'birthdate',
                        '1995-09-10'
                    )
                    ->count()
            );
        }
    }
}
