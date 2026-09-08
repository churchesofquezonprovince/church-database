<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'shepherding_contacts',
            function (Blueprint $table): void {
                $table->time('contact_time')
                    ->nullable()
                    ->after('contact_date');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'shepherding_contacts',
            function (Blueprint $table): void {
                $table->dropColumn('contact_time');
            }
        );
    }
};
