<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'attendance_meeting_form_questions',
            function (Blueprint $table): void {
                $table
                    ->string('database_field', 100)
                    ->nullable()
                    ->after('question_type')
                    ->index();

                $table
                    ->boolean('allow_correction')
                    ->default(false)
                    ->after('is_required');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'attendance_meeting_form_questions',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'database_field',
                ]);

                $table->dropColumn([
                    'database_field',
                    'allow_correction',
                ]);
            }
        );
    }
};
