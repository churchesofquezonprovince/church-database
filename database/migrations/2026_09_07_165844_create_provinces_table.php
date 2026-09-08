<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('country_id')
                ->constrained('countries')
                ->restrictOnDelete();

            $table->string('name', 150);
            $table->string('code', 30)->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->unique([
                'country_id',
                'name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
