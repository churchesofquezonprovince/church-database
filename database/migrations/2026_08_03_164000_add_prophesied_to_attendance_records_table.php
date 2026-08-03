<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('attendance_records', 'prophesied')) {
                $table->boolean('prophesied')
                    ->default(false)
                    ->after('status')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            if (Schema::hasColumn('attendance_records', 'prophesied')) {
                $table->dropColumn('prophesied');
            }
        });
    }
};
