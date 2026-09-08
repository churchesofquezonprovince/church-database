<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 255);
            $table->string('short_name', 100)->nullable();

            $table->foreignId('province_id')
                ->nullable()
                ->constrained('provinces')
                ->restrictOnDelete();

            $table->string('city_municipality', 150)->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index('name');
            $table->index([
                'province_id',
                'city_municipality',
            ]);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
