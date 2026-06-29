<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

public function up(): void
{
    Schema::table('persons', function (Blueprint $table) {

        $table->unsignedInteger('spouse_id')
            ->nullable()
            ->after('household_id');

        $table->foreign('spouse_id')
            ->references('id')
            ->on('persons')
            ->nullOnDelete();

    });
}

    /**
     * Reverse the migrations.
     */
public function down(): void
{
    Schema::table('persons', function (Blueprint $table) {

        $table->dropForeign(['spouse_id']);
        $table->dropColumn('spouse_id');

    });
}
};
