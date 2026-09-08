<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localities', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('province_id')
                ->constrained('provinces')
                ->restrictOnDelete();

            $table->string('name', 150);

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->unique([
                'province_id',
                'name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localities');
    }
};
