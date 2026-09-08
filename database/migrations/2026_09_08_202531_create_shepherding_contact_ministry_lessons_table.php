<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_ministry_lessons',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                $table->unsignedBigInteger(
                    'ministry_lesson_id'
                );

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'scml_contact_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->foreign(
                    'ministry_lesson_id',
                    'scml_lesson_fk'
                )
                    ->references('id')
                    ->on('ministry_lessons')
                    ->restrictOnDelete();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'ministry_lesson_id',
                    ],
                    'scml_contact_lesson_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_ministry_lessons'
        );
    }
};
