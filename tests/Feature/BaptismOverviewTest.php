<?php

namespace Tests\Feature;

use App\Filament\Pages\BaptismOverview;
use App\Models\User;
use App\Services\BaptismOverviewData;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BaptismOverviewTest extends TestCase
{
    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Baptism tests require an isolated SQLite :memory: database.');
        }
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'session.driver' => 'array']);
        Schema::create('users', function (Blueprint $t): void {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('password');
            $t->string('role'); $t->rememberToken(); $t->timestamps();
        });
        Schema::create('persons', function (Blueprint $t): void {
            $t->increments('id'); $t->string('firstname'); $t->string('middlename')->nullable();
            $t->string('lastname'); $t->string('suffix')->nullable(); $t->string('locality')->nullable();
        });
        Schema::create('church_profiles', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('person_id')->unique(); $t->date('baptism_date')->nullable();
            $t->integer('baptism_year')->nullable(); $t->integer('baptism_month')->nullable(); $t->integer('baptism_day')->nullable();
        });
        Schema::create('attendance_sheets', function (Blueprint $t): void {
            $t->id(); $t->string('title'); $t->string('locality')->nullable();
        });
        Schema::create('schedules', function (Blueprint $t): void { $t->id(); $t->string('title'); });
        Schema::create('attendance_sessions', function (Blueprint $t): void {
            $t->increments('id'); $t->unsignedBigInteger('attendance_sheet_id'); $t->unsignedBigInteger('schedule_id')->nullable();
            $t->date('session_date'); $t->string('session_time')->nullable(); $t->string('title')->nullable();
            $t->string('location')->nullable(); $t->boolean('is_no_meeting')->default(false);
        });
        Schema::create('attendance_records', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('attendance_session_id'); $t->unsignedBigInteger('person_id');
            $t->string('status')->nullable(); $t->boolean('is_present')->nullable();
        });
        $migration = require database_path('migrations/2026_09_28_210000_create_baptism_activity_links_table.php');
        $migration->up();
        DB::table('users')->insert(['id' => 1, 'name' => 'Test Admin', 'email' => 'admin@example.test', 'password' => 'unused', 'role' => 'admin']);
        $this->actingAs(User::findOrFail(1));
        Filament::setCurrentPanel(Filament::getPanel('quezonprovinceactivities'));
        DB::table('attendance_sheets')->insert(['id' => 1, 'title' => 'Gospel Meeting', 'locality' => 'Lucena']);
    }

    private function baptismFixturePerson(int $id, ?int $y, ?int $m, ?int $d, string $locality = 'Lucena'): void
    {
        DB::table('persons')->insert(['id' => $id, 'firstname' => 'Person '.$id, 'lastname' => 'Test', 'locality' => $locality]);
        DB::table('church_profiles')->insert(['person_id' => $id, 'baptism_year' => $y, 'baptism_month' => $m, 'baptism_day' => $d]);
    }

    private function baptismFixtureSession(int $id, string $date = '2026-09-28', bool $cancelled = false): void
    {
        DB::table('attendance_sessions')->insert(['id' => $id, 'attendance_sheet_id' => 1,
            'session_date' => $date, 'is_no_meeting' => $cancelled]);
    }

    private function baptismFixturePage(): BaptismOverview
    {
        $page = new BaptismOverview(); $page->mount(); $page->year = 2026; $page->month = 9;
        return $page;
    }

    private function baptismViewData(BaptismOverview $page): array
    {
        return (new \ReflectionMethod($page, 'getViewData'))->invoke($page);
    }

    public function test_partial_dates_do_not_invent_missing_components(): void
    {
        foreach ([[2026, null, 28], [null, 9, 28], [2026, 9, null], [null, null, 28]] as [$y, $m, $d]) {
            $p = BaptismOverviewData::dateParts((object) ['baptism_year' => $y, 'baptism_month' => $m, 'baptism_day' => $d, 'baptism_date' => '2026-09-28']);
            $this->assertNull($p['date']); $this->assertSame($y, $p['year']); $this->assertSame($m, $p['month']);
        }
        $legacy = BaptismOverviewData::dateParts((object) ['baptism_date' => '2024-02-29']);
        $this->assertSame('2024-02-29', $legacy['date']);
        $invalid = BaptismOverviewData::dateParts((object) ['baptism_year' => 2025, 'baptism_month' => 2, 'baptism_day' => 29]);
        $this->assertNull($invalid['date']);
    }

    public function test_year_month_and_unknown_year_totals_are_separate(): void
    {
        $this->baptismFixturePerson(1, 2026, 9, 28); $this->baptismFixturePerson(2, 2026, 9, null);
        $this->baptismFixturePerson(3, 2026, null, 28); $this->baptismFixturePerson(4, null, 9, 28);
        $this->baptismFixturePerson(5, 2025, 9, 28); $this->baptismFixturePerson(6, null, null, null);
        $page = $this->baptismFixturePage(); $data = $this->baptismViewData($page);
        $this->assertSame(3, $data['total']); $this->assertSame(1, $data['completeCount']);
        $this->assertSame(2, $data['partialCount']); $this->assertSame(1, $data['unknownYearCount']);
        $page->display = 'month'; $data = $this->baptismViewData($page);
        $this->assertSame(2, $data['total']); $this->assertSame(1, $data['partialCount']);
        $page->showList('unknown-year'); $data = $this->baptismViewData($page);
        $this->assertSame([4], $data['people']->pluck('person_id')->all());
    }

    public function test_heatmap_handles_leap_years_and_counts_each_person_once(): void
    {
        $this->baptismFixturePerson(1, 2024, 2, 29); $this->baptismFixturePerson(2, 2024, 2, 29);
        $page = $this->baptismFixturePage(); $page->year = 2024; $data = $this->baptismViewData($page);
        $feb = $data['calendars'][1];
        $this->assertCount(29, $feb['days']); $this->assertSame(2, $feb['days'][28]['count']);
        $page->year = 2025; $this->assertCount(28, $this->baptismViewData($page)['calendars'][1]['days']);
    }

    public function test_same_day_sessions_do_not_automatically_confirm_baptisms(): void
    {
        $this->baptismFixturePerson(1, 2026, 9, 28); $this->baptismFixtureSession(1); $this->baptismFixtureSession(2, '2026-09-28', true);
        DB::table('attendance_records')->insert(['person_id' => 1, 'attendance_session_id' => 1, 'status' => 'present', 'is_present' => true]);
        $page = $this->baptismFixturePage(); $page->selectDay('2026-09-28'); $data = $this->baptismViewData($page);
        $this->assertSame(0, $data['linkedCount']); $this->assertCount(1, $data['daySessions']);
        $this->assertTrue($data['people'][0]['suggestions'][0]['attended']);
        $this->assertSame(0, DB::table('baptism_activity_links')->count());
    }

    public function test_confirm_replace_and_unlink_keep_baptism_fields(): void
    {
        $this->baptismFixturePerson(1, 2026, 9, 28); $this->baptismFixtureSession(1); $this->baptismFixtureSession(2);
        $before = (array) DB::table('church_profiles')->first();
        $service = new BaptismOverviewData(); $service->confirm(1, 1, '2026-09-28');
        $service->confirm(1, 2, '2026-09-28');
        $this->assertSame(1, DB::table('baptism_activity_links')->count());
        $this->assertSame(2, (int) DB::table('baptism_activity_links')->value('attendance_session_id'));
        $this->assertSame(1, $this->baptismViewData($this->baptismFixturePage())['linkedCount']);
        $service->unlink(1); $this->assertSame(0, DB::table('baptism_activity_links')->count());
        $this->assertSame($before, (array) DB::table('church_profiles')->first());
    }

    public function test_invalid_links_are_rejected(): void
    {
        $this->baptismFixturePerson(1, 2026, 9, 28); $this->baptismFixturePerson(2, 2026, 9, null);
        $this->baptismFixtureSession(1); $this->baptismFixtureSession(2, '2026-09-29'); $this->baptismFixtureSession(3, '2026-09-28', true);
        foreach ([[1, 2, '2026-09-28'], [1, 3, '2026-09-28'], [2, 1, '2026-09-28'], [1, 1, '2026-09-27'], [999, 1, '2026-09-28']] as [$person, $session, $date]) {
            try { (new BaptismOverviewData())->confirm($person, $session, $date); $this->fail('Invalid link accepted.'); }
            catch (ValidationException $e) { $this->assertArrayHasKey('activity', $e->errors()); }
        }
        $this->assertSame(0, DB::table('baptism_activity_links')->count());
    }

    public function test_changed_profile_or_session_date_excludes_stale_links(): void
    {
        $this->baptismFixturePerson(1, 2026, 9, 28); $this->baptismFixtureSession(1);
        (new BaptismOverviewData())->confirm(1, 1, '2026-09-28');
        DB::table('church_profiles')->where('person_id', 1)->update(['baptism_day' => 29]);
        $data = $this->baptismViewData($this->baptismFixturePage()); $this->assertSame(0, $data['linkedCount']);
        $this->assertNotNull($data['people'][0]['link']); $this->assertCount(0, $data['summary']);
        DB::table('church_profiles')->update(['baptism_day' => 28]);
        DB::table('attendance_sessions')->update(['session_date' => '2026-09-29']);
        $this->assertSame(0, $this->baptismViewData($this->baptismFixturePage())['linkedCount']);
        DB::table('attendance_sessions')->update(['session_date' => '2026-09-28', 'is_no_meeting' => true]);
        $this->assertSame(0, $this->baptismViewData($this->baptismFixturePage())['linkedCount']);
    }

    public function test_attendance_status_takes_priority_over_legacy_flag(): void
    {
        $this->assertFalse(BaptismOverviewData::attended((object) ['status' => 'absent', 'is_present' => true]));
        $this->assertFalse(BaptismOverviewData::attended((object) ['status' => 'excused', 'is_present' => true]));
        $this->assertTrue(BaptismOverviewData::attended((object) ['status' => 'late', 'is_present' => false]));
        $this->assertTrue(BaptismOverviewData::attended((object) ['status' => null, 'is_present' => true]));
    }

    public function test_viewer_and_guest_cannot_read_or_write_baptism_data(): void
    {
        $this->baptismFixturePerson(1, 2026, 9, 28); $this->baptismFixtureSession(1);
        DB::table('users')->where('id', 1)->update(['role' => 'viewer']);
        $this->actingAs(User::findOrFail(1)); $this->assertFalse(BaptismOverview::canAccess());
        foreach (['read', 'confirm', 'unlink'] as $action) {
            try {
                $service = new BaptismOverviewData();
                match ($action) { 'read' => $service->records(), 'confirm' => $service->confirm(1, 1, '2026-09-28'), 'unlink' => $service->unlink(1) };
                $this->fail('Viewer access accepted.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        }
        auth()->logout(); $this->assertFalse(BaptismOverview::canAccess());
    }

    public function test_filters_and_pagination_keep_counts_consistent(): void
    {
        for ($id = 1; $id <= 23; $id++) { $this->baptismFixturePerson($id, 2026, 9, 28); }
        $this->baptismFixturePerson(24, 2026, 9, 28, 'Tayabas'); $this->baptismFixtureSession(1);
        (new BaptismOverviewData())->confirm(1, 1, '2026-09-28');
        $page = $this->baptismFixturePage(); $page->locality = 'Lucena'; $data = $this->baptismViewData($page);
        $this->assertSame(23, $data['total']); $this->assertCount(20, $data['people']);
        $page->changePeoplePage(2); $this->assertCount(3, $this->baptismViewData($page)['people']);
        $page->activity = '1'; $this->assertSame(1, $this->baptismViewData($page)['total']);
        $page->activity = 'unlinked'; $this->assertSame(22, $this->baptismViewData($page)['total']);
    }

    public function test_encoder_can_use_page_and_navigation_is_after_shepherding_records(): void
    {
        DB::table('users')->update(['role' => 'encoder']); $this->actingAs(User::findOrFail(1));
        $this->assertTrue(BaptismOverview::canAccess());
        $this->assertSame('Shepherding', BaptismOverview::getNavigationGroup());
        $this->assertSame(35, BaptismOverview::getNavigationSort());
    }

    public function test_livewire_page_renders_and_switches_months(): void
    {
        $this->baptismFixturePerson(1, 2026, 9, 28);
        Livewire::test(BaptismOverview::class)->set('year', 2026)->set('display', 'month')->set('month', 9)
            ->assertSee('September 2026 Baptism Calendar')->call('selectDay', '2026-09-28')
            ->assertSee('Baptisms on 2026-09-28')->assertSee('Test, Person 1');
    }
}
