<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ministry_books', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20)
                ->unique();

            $table->string('title', 255);

            $table->string('short_title', 100)
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();
        });

        DB::table('ministry_books')->insert([
            [
                'code' => 'NB',
                'title' => 'New Believer Series',
                'short_title' => null,
                'description' => null,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'LL',
                'title' => 'Life Lesson',
                'short_title' => null,
                'description' => null,
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'HG',
                'title' => 'High Gospel',
                'short_title' => null,
                'description' => null,
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'AB',
                'title' => 'After Being Saved',
                'short_title' => null,
                'description' => null,
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'TO',
                'title' => 'Trust and Obey',
                'short_title' => null,
                'description' => null,
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'IS',
                'title' => 'Intensified Shepherding',
                'short_title' => null,
                'description' => null,
                'is_active' => true,
                'sort_order' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ministry_books');
    }
};
