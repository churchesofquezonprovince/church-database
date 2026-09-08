<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('province_settings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('primary_country_id')
                ->nullable()
                ->constrained('countries')
                ->restrictOnDelete();

            $table->foreignId('primary_province_id')
                ->nullable()
                ->constrained('provinces')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('province_settings');
    }
};
