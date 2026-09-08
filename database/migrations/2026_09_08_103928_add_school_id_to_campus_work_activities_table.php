<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campus_work_activities', function (Blueprint $table): void {
            $table->foreignId('school_id')
                ->nullable()
                ->after('school_campus')
                ->constrained('schools')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campus_work_activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
