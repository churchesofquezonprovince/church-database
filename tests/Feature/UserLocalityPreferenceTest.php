<?php

namespace Tests\Feature;

use App\Models\User;
use App\Filament\Resources\Users\UserResource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserLocalityPreferenceTest extends TestCase
{
    public function createApplication(): \Illuminate\Foundation\Application
    {
        if (getenv('DB_CONNECTION') !== 'sqlite' || getenv('DB_DATABASE') !== ':memory:') {
            throw new \RuntimeException('Run these tests only with isolated in-memory SQLite.');
        }
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Refusing tests against a non-isolated database.');
        }
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON');
        Schema::create('persons', function (Blueprint $t): void {
            $t->increments('id');
            $t->unsignedBigInteger('locality_id')->nullable();
            $t->string('firstname')->nullable();
            $t->string('lastname')->nullable();
        });
        Schema::create('users', function (Blueprint $t): void {
            $t->id(); $t->string('name'); $t->string('email');
            $t->string('password')->nullable(); $t->string('role'); $t->timestamps();
        });
        Schema::create('localities', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('province_id');
            $t->string('name'); $t->boolean('is_active');
        });
        Schema::create('province_settings', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('primary_province_id')->nullable();
        });
        Schema::create('attendance_sheets', function (Blueprint $t): void {
            $t->increments('id'); $t->string('sheet_type');
            $t->unsignedBigInteger('locality_id')->nullable();
        });
        (require database_path('migrations/2026_09_29_083000_add_person_id_to_users_table.php'))->up();
        DB::table('province_settings')->insert(['primary_province_id' => 1]);
        DB::table('localities')->insert([
            ['id'=>1, 'province_id'=>1, 'name'=>'Lucban', 'is_active'=>true],
            ['id'=>2, 'province_id'=>1, 'name'=>'Gumaca', 'is_active'=>true],
            ['id'=>3, 'province_id'=>2, 'name'=>'Lucban', 'is_active'=>true],
            ['id'=>4, 'province_id'=>1, 'name'=>'Inactive', 'is_active'=>false],
        ]);
        DB::table('persons')->insert(['id'=>10, 'firstname'=>'Test', 'lastname'=>'Person', 'locality_id'=>2]);
    }

    private function preferenceUser(?int $personId = 10, string $role = 'encoder'): User
    {
        return User::create(['name'=>'Test User', 'email'=>'test@example.invalid', 'role'=>$role, 'person_id'=>$personId]);
    }

    public function test_linked_person_supplies_current_locality(): void
    {
        $user = $this->preferenceUser();
        $this->assertSame(10, (int) $user->fresh()->person_id);
        $this->assertSame(10, (int) $user->person->id);
        $this->assertSame('Gumaca', $user->preferredLocalityName());
        DB::table('persons')->where('id', 10)->update(['locality_id'=>1]);
        $this->assertSame('Lucban', $user->preferredLocalityName());
    }

    public function test_explicit_selection_and_existing_fallback_take_priority_correctly(): void
    {
        $user = $this->preferenceUser();
        $choices = ['Lucban', 'Gumaca'];
        $this->assertSame('Lucban', $user->defaultLocalitySelection($choices, 'lucban'));
        $this->assertSame('Gumaca', $user->defaultLocalitySelection($choices));
        $this->assertSame('Lucban', $user->defaultLocalitySelection($choices, '', 'Lucban'));
        $this->assertSame('Lucban', $user->defaultLocalitySelection($choices, ['bad'], 'Lucban'));
        $this->assertSame('Lucban', $user->defaultLocalitySelection(['Lucban'], null, 'Lucban'));
    }

    public function test_unlinked_and_missing_localities_keep_fallback(): void
    {
        $user = $this->preferenceUser(null);
        $this->assertNull($user->preferredLocalityName());
        $this->assertSame('Lucban', $user->defaultLocalitySelection(['Lucban'], null, 'Lucban'));
        $user->update(['person_id'=>10]);
        DB::table('persons')->where('id', 10)->update(['locality_id'=>null]);
        $this->assertNull($user->preferredLocalityName());
    }

    public function test_inactive_or_other_province_localities_are_not_defaults(): void
    {
        $user = $this->preferenceUser();
        foreach ([3, 4] as $id) {
            DB::table('persons')->where('id', 10)->update(['locality_id'=>$id]);
            $this->assertNull($user->preferredLocalityName());
        }
    }

    public function test_home_meeting_default_and_manual_selection(): void
    {
        $this->actingAs($this->preferenceUser());
        // Both localities need People records to appear in existing options.
        DB::table('persons')->insert(['id'=>11, 'locality_id'=>1]);
        $page = new class extends \App\Filament\Pages\HomeMeetingSchedule {
            public function scheduleEntries(): \Illuminate\Support\Collection
            {
                return collect();
            }
        };
        request()->query->replace([]);
        $page->mount();
        $this->assertSame('Gumaca', $page->selectedLocality);
        $page->updatedSelectedLocality('Lucban');
        $this->assertSame('Lucban', $page->selectedLocality);
        request()->query->replace(['locality'=>'Lucban']);
        $page->mount();
        $this->assertSame('Lucban', $page->selectedLocality);
    }

    public function test_prayer_items_explicit_locality_beats_person_default(): void
    {
        $this->actingAs($this->preferenceUser());
        DB::table('persons')->insert(['id'=>11, 'locality_id'=>1]);
        $page = new \App\Filament\Pages\PrayerMeetingItems();
        request()->query->replace([]);
        $this->assertSame('Gumaca', $page->selectedLocality());
        request()->query->replace(['locality'=>'Lucban']);
        $this->assertSame('Lucban', $page->selectedLocality());
    }

    public function test_deleting_person_unlinks_but_keeps_login(): void
    {
        $user = $this->preferenceUser();
        DB::table('persons')->where('id', 10)->delete();
        $this->assertNotNull($user->fresh());
        $this->assertNull($user->fresh()->person_id);
    }

    public function test_link_does_not_change_roles_or_admin_only_user_editing(): void
    {
        $user = $this->preferenceUser();
        $this->actingAs($user);
        $this->assertTrue($user->canManageRecords());
        $this->assertFalse(UserResource::canEdit($user));
        $this->assertFalse(UserResource::canCreate());
        $user->update(['role'=>'admin']);
        $this->assertTrue(UserResource::canEdit($user));
    }
}
