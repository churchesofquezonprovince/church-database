<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('filename');
            $table->string('status')->default('running')->index();
            $table->string('database_name')->nullable();

            $table->string('local_path')->nullable();
            $table->unsignedBigInteger('local_size_bytes')->nullable();

            $table->string('external_path')->nullable();
            $table->string('external_status')->default('pending')->index();
            $table->unsignedBigInteger('external_size_bytes')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
    }
};
