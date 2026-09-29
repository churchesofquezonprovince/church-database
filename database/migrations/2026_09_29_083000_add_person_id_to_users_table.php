<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // persons.id is INT UNSIGNED in this database, not BIGINT.
            $table->unsignedInteger('person_id')->nullable()->index();
            $table->foreign('person_id')->references('id')->on('persons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['person_id']);
            $table->dropColumn('person_id');
        });
    }
};
