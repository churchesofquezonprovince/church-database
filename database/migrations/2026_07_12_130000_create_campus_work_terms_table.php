<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_work_terms', function (Blueprint $table): void {
            $table->increments('id');

            $table->string('academic_year', 20);
            $table->string('semester', 50);
            $table->boolean('is_active')->default(false);

            $table->timestamps();

            $table->unique(
                ['academic_year', 'semester'],
                'campus_work_terms_year_semester_unique'
            );

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_work_terms');
    }
};
