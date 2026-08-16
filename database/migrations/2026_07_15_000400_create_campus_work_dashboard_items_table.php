<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_work_dashboard_items', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('section', 50);
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('link', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('section');
            $table->index('sort_order');
        });

        DB::table('campus_work_dashboard_items')->insert([
            [
                'section' => 'student_book',
                'title' => 'Bridge and Channel',
                'description' => null,
                'link' => null,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section' => 'serving_book',
                'title' => 'Fellowship for the beginning among the work of the students',
                'description' => null,
                'link' => null,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_work_dashboard_items');
    }
};
