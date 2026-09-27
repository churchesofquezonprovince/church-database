<?php

namespace Tests\Feature;

use App\Livewire\AttentionDatabaseNotifications;
use App\Models\{ChildrenWorkLesson, User};
use App\Services\{AttentionGroups, AttentionNotificationService};
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
use Tests\TestCase;

class AttentionNotificationsTest extends TestCase
{
    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('These tests require an isolated SQLite :memory: database.');
        }
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // This suite tests notification behavior, not historical production migrations.
        // createApplication() above rejects any database except SQLite :memory:.
        config(['cache.default' => 'array', 'session.driver' => 'array']);
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('viewer');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('hymnal_net_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('match_status');
            $table->unsignedBigInteger('matched_hymn_id')->nullable();
            $table->unsignedBigInteger('matched_hymn_variant_id')->nullable();
            $table->string('section_code')->nullable();
            $table->string('collection_code')->nullable();
            $table->string('number')->nullable();
            $table->timestamps();
        });
        Schema::create('hymns', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('hymn_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hymn_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('hymn_addition_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->text('source_url')->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_sheets', function (Blueprint $table): void {
            $table->id();
            $table->string('sheet_type');
            $table->boolean('is_active')->default(true);
            $table->string('meeting_form_type')->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_sheet_id');
            $table->date('session_date');
            $table->timestamps();
        });
        Schema::create('attendance_meeting_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_session_id');
            $table->string('submitted_form_type')->nullable();
            $table->string('respondent_type')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('response')->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_sheet_id');
            $table->unsignedBigInteger('person_id');
            $table->boolean('is_active')->default(true);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });
        Schema::create('children_work_lessons', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->date('scheduled_on')->nullable();
            $table->string('lesson_title')->nullable();
            $table->text('memory_verse')->nullable();
            $table->text('story_url')->nullable();
            $table->timestamps();
        });
        Schema::create('problem_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->timestamps();
        });
    }

    private function login(string $role = 'admin'): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Bell Test', 'email' => Str::uuid().'@example.test',
            'password' => 'unused-test-password', 'role' => $role,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::query()->findOrFail($id);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('quezonprovinceactivities'));
        return $user;
    }

    private function fixture(array $ids = ['1', '2']): AttentionGroups
    {
        return new class($ids) extends AttentionGroups {
            public array $ids;
            public bool $permitted = true;
            public bool $fail = false;
            public function __construct(array $ids) { $this->ids = $ids; }
            public function allowed(): array { return $this->permitted ? ['hymns' => true] : []; }
            public function get(string $key): array {
                if ($this->fail) { throw new \RuntimeException('Fixture query unavailable'); }
                return ['title' => 'Needs review', 'body' => count($this->ids).' pending',
                    'members' => $this->ids, 'url' => '/quezonprovinceactivities/hymns-setup'];
            }
        };
    }

    public function test_sync_groups_work_without_duplicate_notifications(): void
    {
        $user = $this->login();
        $service = new AttentionNotificationService($this->fixture());
        $service->sync($user);
        $service->sync($user);
        $this->assertSame(1, $user->notifications()->count());
        $record = $user->notifications()->firstOrFail();
        $this->assertSame('filament', $record->data['format']);
        $this->assertSame(['1', '2'], $record->data['attention']['members']);
        $this->assertNull($record->read_at);
    }

    public function test_read_state_survives_count_decrease_but_new_work_resets_it(): void
    {
        $user = $this->login();
        $groups = $this->fixture();
        $service = new AttentionNotificationService($groups);
        $service->sync($user);
        $record = $user->notifications()->firstOrFail();
        $record->markAsRead();
        $groups->ids = ['2'];
        $service->sync($user);
        $this->assertNotNull($record->fresh()->read_at);
        $groups->ids = ['3']; // Same count, different outstanding work.
        $service->sync($user);
        $this->assertNull($record->fresh()->read_at);
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_dismissal_stays_hidden_until_new_work_arrives(): void
    {
        $user = $this->login();
        $groups = $this->fixture();
        $service = new AttentionNotificationService($groups);
        $this->app->instance(AttentionNotificationService::class, $service);
        $service->sync($user);
        $record = $user->notifications()->firstOrFail();
        $bell = new AttentionDatabaseNotifications();
        $bell->removeNotification($record->id);
        $service->sync($user);
        $this->assertTrue($record->fresh()->data['attention']['dismissed']);
        $this->assertSame(0, (new AttentionDatabaseNotifications())->getNotificationsQuery()->count());
        $groups->ids[] = '3';
        $service->sync($user);
        $this->assertFalse($record->fresh()->data['attention']['dismissed']);
        $this->assertSame(1, (new AttentionDatabaseNotifications())->getUnreadNotificationsCount());
    }

    public function test_resolved_work_is_removed_and_announcements_are_preserved(): void
    {
        $user = $this->login();
        $groups = $this->fixture();
        $service = new AttentionNotificationService($groups);
        $announcement = $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'announcement',
            'data' => ['format' => 'filament', 'title' => 'Major update'],
        ]);
        $service->sync($user);
        $groups->ids = [];
        $service->sync($user);
        $this->assertSame(1, $user->notifications()->count());
        $this->assertNotNull($announcement->fresh());
    }

    public function test_permission_removal_removes_managed_entry(): void
    {
        $user = $this->login();
        $groups = $this->fixture();
        $service = new AttentionNotificationService($groups);
        $service->sync($user);
        $groups->permitted = false;
        $service->sync($user);
        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_failed_count_does_not_remove_an_existing_entry(): void
    {
        $user = $this->login();
        $groups = $this->fixture();
        $service = new AttentionNotificationService($groups);
        $service->sync($user);
        $groups->fail = true;
        $service->sync($user);
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_notification_actions_cannot_modify_another_users_entry(): void
    {
        $owner = $this->login();
        $service = new AttentionNotificationService($this->fixture());
        $service->sync($owner);
        $record = $owner->notifications()->firstOrFail();
        $other = $this->login();
        $service->dismiss($other, $record->id);
        $this->assertFalse($record->fresh()->data['attention']['dismissed']);
        $this->assertNull($record->fresh()->read_at);
    }

    public function test_actual_page_permissions_limit_categories(): void
    {
        $this->login('viewer');
        $groups = new AttentionGroups();
        $this->assertSame([], $groups->allowed());
        $this->login('encoder');
        $this->assertSame(['attendance', 'children'], array_keys($groups->allowed()));
        $this->login('admin');
        $this->assertSame(AttentionGroups::KEYS, array_keys($groups->allowed()));
    }

    public function test_story_title_without_link_is_still_missing_material(): void
    {
        $lesson = new ChildrenWorkLesson(['memory_verse' => 'A verse', 'story' => 'Story title']);
        $this->assertSame(['story link'], AttentionGroups::missingMaterials($lesson));
        $lesson->story_url = 'https://example.com/story';
        $this->assertSame([], AttentionGroups::missingMaterials($lesson));
        $lesson->memory_verse = ' ';
        $this->assertSame(['memory verse'], AttentionGroups::missingMaterials($lesson));
    }

    public function test_category_queries_run_against_isolated_review_fixtures(): void
    {
        $this->login();
        config(['cache.default' => 'array']);
        $groups = new AttentionGroups();
        foreach (AttentionGroups::KEYS as $key) {
            \Illuminate\Support\Facades\Cache::forget('coqp:attention:v1:'.$key);
            $group = $groups->get($key);
            $this->assertIsArray($group['members']);
            $this->assertNotEmpty($group['url']);
        }
    }

    public function test_livewire_bell_renders_and_marks_a_group_read(): void
    {
        $user = $this->login();
        $this->app->instance(AttentionNotificationService::class,
            new AttentionNotificationService($this->fixture()));
        $bell = \Livewire\Livewire::test(AttentionDatabaseNotifications::class);
        $id = $user->notifications()->firstOrFail()->id;
        $bell->call('markNotificationAsRead', $id)->assertSuccessful();
        $this->assertNotNull($user->notifications()->whereKey($id)->firstOrFail()->read_at);
    }

    public function test_shared_attendance_rules_keep_identity_and_participant_reviews(): void
    {
        $this->login();
        DB::table('attendance_participants')->insert([
            ['attendance_sheet_id' => 1, 'person_id' => 1, 'is_active' => true, 'starts_on' => null, 'ends_on' => null],
            ['attendance_sheet_id' => 1, 'person_id' => 2, 'is_active' => false, 'starts_on' => null, 'ends_on' => null],
            ['attendance_sheet_id' => 1, 'person_id' => 3, 'is_active' => true, 'starts_on' => null, 'ends_on' => '2026-09-26'],
        ]);
        $responseClass = \App\Models\AttendanceMeetingResponse::class;
        $responses = collect();
        foreach ([1, 2, 3, 4, 5] as $personId) {
            $responses->push((new $responseClass())->forceFill([
                'id' => $personId, 'person_id' => $personId,
                'respondent_type' => $responseClass::RESPONDENT_PERSON,
                'response' => $personId === 5 ? $responseClass::RESPONSE_NO : $responseClass::RESPONSE_YES,
            ]));
        }
        $responses->push((new $responseClass())->forceFill([
            'id' => 6, 'person_id' => null, 'respondent_type' => 'guest',
            'response' => $responseClass::RESPONSE_NO,
        ]));
        $session = new \App\Models\AttendanceSession([
            'attendance_sheet_id' => 1, 'session_date' => '2026-09-27',
        ]);
        $statuses = \App\Support\MeetingResponseAttentionWorkflow::statuses($responses, $session);
        $this->assertSame([2, 3, 4, 6], $statuses->filter(fn ($row) => $row['needs_action'])->keys()->all());
        $this->assertSame('participant_covered', $statuses[1]['key']);
        $this->assertSame('no_participant_needed', $statuses[5]['key']);
        $this->assertSame('needs_identity_review', $statuses[6]['key']);
    }

    public function test_children_reminders_only_cover_upcoming_non_draft_incomplete_lessons(): void
    {
        $this->login();
        foreach ([
            [1, 'scheduled', today()->toDateString(), null, null],
            [2, 'scheduled', today()->addDays(7)->toDateString(), 'Verse', null],
            [3, 'scheduled', today()->addDays(8)->toDateString(), null, null],
            [4, 'draft', today()->toDateString(), null, null],
            [5, 'cancelled', today()->toDateString(), null, null],
            [6, 'scheduled', today()->toDateString(), 'Verse', 'https://example.com/story'],
        ] as [$id, $status, $date, $verse, $story]) {
            DB::table('children_work_lessons')->insert(['id' => $id, 'status' => $status,
                'scheduled_on' => $date, 'memory_verse' => $verse, 'story_url' => $story]);
        }
        $group = (new AttentionGroups())->get('children');
        $this->assertCount(3, $group['members']);
        $this->assertStringStartsWith('2 upcoming lessons', $group['body']);
        $this->assertStringContainsString('lesson=1', $group['url']);
    }

    public function test_hymn_requests_and_problem_groups_filter_completed_work(): void
    {
        $this->login();
        foreach (['unmatched', 'ambiguous', 'conflict', 'linked'] as $status) {
            DB::table('hymnal_net_entries')->insert(['match_status' => $status, 'number' => '1']);
        }
        $pending = \App\Models\HymnAdditionRequest::STATUS_PENDING;
        DB::table('hymn_addition_requests')->insert([
            ['status' => $pending, 'source_url' => 'https://example.com/hymn'],
            ['status' => $pending, 'source_url' => ''],
            ['status' => $pending, 'source_url' => null],
            ['status' => 'approved', 'source_url' => 'https://example.com/done'],
        ]);
        DB::table('problem_reports')->insert([['status' => 'new'], ['status' => 'checking'], ['status' => 'resolved']]);
        $groups = new AttentionGroups();
        $this->assertCount(3, $groups->get('hymns')['members']);
        $this->assertCount(1, $groups->get('external_hymns')['members']);
        $this->assertCount(1, $groups->get('problems')['members']);
    }

    public function test_panel_uses_custom_bell_component(): void
    {
        $this->assertSame(AttentionDatabaseNotifications::class,
            Filament::getPanel('quezonprovinceactivities')->getDatabaseNotificationsLivewireComponent());
    }
}
