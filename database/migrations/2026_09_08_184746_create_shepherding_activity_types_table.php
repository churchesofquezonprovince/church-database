<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shepherding_activity_types', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20)
                ->unique();

            $table->string('name', 100);

            $table->string('category', 50)
                ->index();

            $table->text('description')
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();
        });

        DB::table('shepherding_activity_types')->insert([
            [
                'code' => 'V',
                'name' => 'Visitation',
                'category' => 'Shepherding',
                'description' => null,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'P',
                'name' => 'Prayer',
                'category' => 'Shepherding',
                'description' => null,
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'S',
                'name' => 'Sharing',
                'category' => 'Shepherding',
                'description' => null,
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'F',
                'name' => 'Fellowship',
                'category' => 'Shepherding',
                'description' => null,
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MR',
                'name' => 'Morning Revival',
                'category' => 'Spiritual Practice',
                'description' => null,
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BR',
                'name' => 'Bible Reading',
                'category' => 'Spiritual Practice',
                'description' => null,
                'is_active' => true,
                'sort_order' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'HS',
                'name' => 'Hymn Singing',
                'category' => 'Spiritual Practice',
                'description' => null,
                'is_active' => true,
                'sort_order' => 70,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CO',
                'name' => 'Contact',
                'category' => 'Gospel Work',
                'description' => null,
                'is_active' => true,
                'sort_order' => 80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PS',
                'name' => 'Pursuance',
                'category' => 'Gospel Work',
                'description' => null,
                'is_active' => true,
                'sort_order' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GT',
                'name' => 'Gospel Tract',
                'category' => 'Gospel Work',
                'description' => null,
                'is_active' => true,
                'sort_order' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EM',
                'name' => 'E-manna',
                'category' => 'Gospel Work',
                'description' => null,
                'is_active' => true,
                'sort_order' => 110,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shepherding_activity_types');
    }
};
