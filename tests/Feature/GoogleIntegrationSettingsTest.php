<?php

namespace Tests\Feature;

use App\Http\Controllers\GoogleIntegrationSettingsController;
use App\Models\DeveloperSetting;
use App\Services\GoogleIntegrationSettings as Settings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleIntegrationSettingsTest extends TestCase
{
    private array $accountPaths = [];
    private string $testStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testStorage = sys_get_temp_dir().'/coqp-google-tests-'.bin2hex(random_bytes(8));
        mkdir($this->testStorage, 0700, true);
        $this->app->useStoragePath($this->testStorage);
        // A dedicated in-memory connection: never migrate or clear the real database.
        config(['database.connections.google_setup_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('google_setup_test');
        Schema::create('developer_settings', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->text('value')->nullable(); $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->accountPaths as $path) {
            if (is_file($path)) { unlink($path); }
        }
        DB::purge('google_setup_test');
        \Illuminate\Support\Facades\File::deleteDirectory($this->testStorage);
        parent::tearDown();
    }

    private function saveAsAdmin(array $data)
    {
        $request = Request::create('/internal/google-integrations', 'POST', $data);
        $request->setUserResolver(fn () => new class {
            public function isAdmin(): bool { return true; }
        });
        return app(GoogleIntegrationSettingsController::class)->store($request);
    }

    public function test_defaults_are_used_until_an_override_is_saved(): void
    {
        config(['services.google_calendar.enabled' => true, 'children_work.google_sheets.enabled' => true]);
        $this->assertTrue(Settings::get('services.google_calendar.enabled'));
        DeveloperSetting::putValue('google_integration_calendar', json_encode(['enabled' => false]));
        $this->assertFalse(Settings::get('services.google_calendar.enabled'));
        $this->assertTrue(Settings::get('children_work.google_sheets.enabled'));
        $this->assertTrue(config('services.google_calendar.enabled'));
    }

    public function test_empty_calendar_override_disables_legacy_fallback(): void
    {
        config(['services.google_calendar.calendar_id' => 'legacy@example.com']);
        $this->saveAsAdmin(['feature' => 'calendar', 'account' => '', 'enabled' => '0', 'calendars' => []]);
        $this->assertNull(Settings::get('services.google_calendar.calendar_id'));
        $this->assertSame([], Settings::get('services.google_calendar.calendars'));
        $this->assertNull(Settings::saved('children'));
        $this->assertNull(Settings::saved('minutes'));
    }

    public function test_duplicate_calendar_ids_are_rejected_before_saving(): void
    {
        $this->saveAsAdmin(['feature' => 'calendar', 'account' => '', 'enabled' => '0', 'calendars' => [
            ['name' => 'First', 'id' => 'same@example.com', 'color' => '#112233'],
            ['name' => 'Second', 'id' => 'same@example.com', 'color' => '#445566'],
        ]]);
        $this->assertNull(Settings::saved('calendar'));
    }

    public function test_account_path_traversal_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        Settings::accountPath('../../somewhere');
    }

    public function test_malformed_credentials_do_not_register_an_account(): void
    {
        try {
            Settings::addAccount('Bad', '{"type":"service_account"}');
            $this->fail('Invalid credentials were accepted.');
        } catch (\RuntimeException) {
            $this->assertSame(0, DeveloperSetting::query()->count());
        }
    }

    public function test_adding_account_keeps_settings_and_stores_only_metadata_in_database(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        $id = Settings::addAccount('Test account', json_encode([
            'type' => 'service_account', 'client_id' => '123456789',
            'client_email' => 'test@example-project.iam.gserviceaccount.com', 'private_key' => $privateKey,
        ]));
        $path = Settings::accountPath($id);
        $this->accountPaths[] = $path;
        $this->assertFileExists($path);
        $this->assertSame(0600, fileperms($path) & 0777);
        $this->assertStringNotContainsString('PRIVATE KEY', DeveloperSetting::string('google_account_'.$id));
        $this->assertNull(Settings::saved('calendar'));
        $this->assertNull(Settings::saved('children'));
        $this->assertNull(Settings::saved('minutes'));
        $this->assertSame('123456789', Settings::credentialsAt($path)['client_id']);
    }

    public function test_non_admin_cannot_save_settings(): void
    {
        $request = Request::create('/internal/google-integrations', 'POST', ['feature' => 'calendar']);
        $request->setUserResolver(fn () => new class {
            public function isAdmin(): bool { return false; }
        });
        try {
            app(GoogleIntegrationSettingsController::class)->store($request);
            $this->fail('Non-admin was accepted.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame(0, DeveloperSetting::query()->count());
        }
    }
}
