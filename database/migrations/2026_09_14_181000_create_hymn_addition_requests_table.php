<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'hymn_addition_requests',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('requested_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('title');

                $table->string(
                    'language',
                    100
                )->nullable();

                $table->longText(
                    'lyrics'
                )->nullable();

                $table->string(
                    'source_url',
                    2048
                )->nullable();

                $table->string(
                    'book_name'
                )->nullable();

                $table->string(
                    'hymn_number',
                    100
                )->nullable();

                $table->string(
                    'status',
                    20
                )
                    ->default('pending')
                    ->index();

                $table->foreignId('reviewed_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'reviewed_at'
                )->nullable();

                $table->text(
                    'review_note'
                )->nullable();

                $table->foreignId('created_hymn_id')
                    ->nullable()
                    ->constrained('hymns')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index(
                    [
                        'requested_by_id',
                        'status',
                    ],
                    'hymn_requests_requester_status_idx'
                );

                $table->index(
                    [
                        'status',
                        'created_at',
                    ],
                    'hymn_requests_status_created_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'hymn_addition_requests'
        );
    }
};
