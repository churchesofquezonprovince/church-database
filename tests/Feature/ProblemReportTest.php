<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\ProblemReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
class ProblemReportTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        (require database_path('migrations/2026_09_21_120000_create_problem_reports_table.php'))->up();
        Storage::fake('local');
    }
    private function payload(): array { return ['category'=>'not_working','description'=>'The attendance button did not open.', 'page_path'=>'/meeting/example']; }
    public function test_guest_can_report_on_short_domain(): void {
        $this->postJson('https://m.overcomers.win/problem-reports/submit', $this->payload())->assertCreated();
        $this->assertDatabaseHas('problem_reports', ['status'=>'new','user_id'=>null,'page_path'=>'/meeting/example']);
    }
    public function test_query_strings_and_non_images_are_rejected(): void {
        $data = $this->payload(); $data['page_path'] = '/meeting/example?token=secret';
        $this->postJson('/problem-reports/submit', $data)->assertStatus(422);
        $data = $this->payload(); $data['screenshot'] = UploadedFile::fake()->create('bad.svg', 5, 'image/svg+xml');
        $this->postJson('/problem-reports/submit', $data)->assertStatus(422);
        $this->assertDatabaseCount('problem_reports', 0);
    }
    public function test_screenshot_is_private_and_admin_only(): void {
        $data = $this->payload();
        $data['screenshot'] = UploadedFile::fake()->createWithContent('screen.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $this->postJson('/problem-reports/submit', $data)->assertCreated();
        $report = ProblemReport::firstOrFail();
        Storage::disk('local')->assertExists($report->screenshot_path);
        $url = '/problem-reports/'.$report->id.'/screenshot';
        $this->get($url)->assertForbidden();
        $viewer = new User(); $viewer->forceFill(['id'=>10,'name'=>'Viewer','role'=>'viewer']);
        $this->actingAs($viewer)->get($url)->assertForbidden();
        $admin = new User(); $admin->forceFill(['id'=>11,'name'=>'Admin','role'=>'admin']);
        $this->actingAs($admin)->get($url)->assertOk();
    }
    public function test_submission_rate_limit(): void {
        for ($i = 0; $i < 5; $i++) $this->postJson('/problem-reports/submit', $this->payload())->assertCreated();
        $this->postJson('/problem-reports/submit', $this->payload())->assertStatus(429);
        $this->assertDatabaseCount('problem_reports', 5);
    }
    public function test_signed_in_reporter_cannot_be_impersonated(): void {
        $viewer = new User(); $viewer->forceFill(['id'=>10,'name'=>'Actual Reporter','role'=>'viewer']);
        $data = $this->payload(); $data['reporter_name'] = 'Someone else';
        $this->actingAs($viewer)->postJson('/problem-reports/submit', $data)->assertCreated();
        $this->assertDatabaseHas('problem_reports', ['user_id'=>10,'reporter_name'=>'Actual Reporter']);
    }
    public function test_status_change_requires_admin(): void {
        $report = ProblemReport::create($this->payload());
        $viewer = new User(); $viewer->forceFill(['id'=>10,'name'=>'Viewer','role'=>'viewer']);
        $this->actingAs($viewer);
        $handler = new class { use \App\Filament\Concerns\ManagesProblemReports; };
        try { $handler->changeProblemStatus($report->id, 'resolved'); $this->fail('Viewer changed status'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        $this->assertSame('new', $report->fresh()->status);
    }
}
