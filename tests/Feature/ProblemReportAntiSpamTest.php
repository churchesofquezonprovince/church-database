<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProblemReportAntiSpamTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('problem_reports');

        Schema::create(
            'problem_reports',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->unsignedBigInteger('user_id')
                    ->nullable()
                    ->index();

                $table
                    ->string('reporter_name', 120)
                    ->nullable();

                $table
                    ->string('contact', 200)
                    ->nullable();

                $table->string('category', 40);

                $table->text('description');

                $table->string('page_path', 1500);

                $table
                    ->string('screenshot_path')
                    ->nullable();

                $table
                    ->string('status', 30)
                    ->default('new')
                    ->index();

                $table->timestamps();
            }
        );
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('problem_reports');

        parent::tearDown();
    }

    public function test_legitimate_guest_problem_report_is_saved(): void
    {
        $response = $this->postJson(
            route('problem-reports.store'),
            [
                'category' => 'not_working',
                'description' =>
                    'The public Children Work page is not working correctly.',
                'reporter_name' => 'Public Tester',
                'contact' => 'tester@example.com',
                'page_path' => '/children-work',
                'report_elapsed_ms' => 5000,
                'website' => '',
            ],
        );

        $response
            ->assertCreated()
            ->assertJson([
                'message' =>
                    'Thank you. Your report has been received.',
            ]);

        $reference = $response->json('reference');

        $this->assertIsInt($reference);

        $this->assertDatabaseHas(
            'problem_reports',
            [
                'id' => $reference,
                'user_id' => null,
                'reporter_name' => 'Public Tester',
                'contact' => 'tester@example.com',
                'category' => 'not_working',
                'page_path' => '/children-work',
                'status' => 'new',
            ],
        );
    }

    public function test_honeypot_submission_is_silently_ignored(): void
    {
        $description =
            'Automated honeypot submission should never be stored.';

        $response = $this->postJson(
            route('problem-reports.store'),
            [
                'category' => 'other',
                'description' => $description,
                'reporter_name' => 'Spam Bot',
                'page_path' => '/',
                'report_elapsed_ms' => 5000,
                'website' => 'https://spam.example',
            ],
        );

        $response
            ->assertCreated()
            ->assertJson([
                'message' =>
                    'Thank you. Your report has been received.',
                'reference' => null,
            ]);

        $this->assertDatabaseMissing(
            'problem_reports',
            [
                'description' => $description,
            ],
        );
    }

    public function test_too_fast_guest_submission_is_silently_ignored(): void
    {
        $description =
            'Automated instant submission should never be stored.';

        $response = $this->postJson(
            route('problem-reports.store'),
            [
                'category' => 'cannot_find',
                'description' => $description,
                'reporter_name' => 'Fast Bot',
                'page_path' => '/',
                'report_elapsed_ms' => 250,
                'website' => '',
            ],
        );

        $response
            ->assertCreated()
            ->assertJson([
                'message' =>
                    'Thank you. Your report has been received.',
                'reference' => null,
            ]);

        $this->assertDatabaseMissing(
            'problem_reports',
            [
                'description' => $description,
            ],
        );
    }
}
