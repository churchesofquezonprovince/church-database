<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['persons', 'households', 'attendance_sheets'] as $table) {
            DB::table($table)
                ->where('locality', 'Lucena')
                ->update(['locality' => 'Lucena City']);
        }
    }

    public function down(): void
    {
        foreach (['persons', 'households', 'attendance_sheets'] as $table) {
            DB::table($table)
                ->where('locality', 'Lucena City')
                ->update(['locality' => 'Lucena']);
        }
    }
};
