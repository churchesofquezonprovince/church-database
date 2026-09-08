<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_profiles', function (Blueprint $table) {
            $table->string('contact_origin', 100)
                ->nullable()
                ->after('introduced_by_id')
                ->index();

            $table->date('first_contact_date')
                ->nullable()
                ->after('contact_origin')
                ->index();

            $table->text('contact_origin_details')
                ->nullable()
                ->after('first_contact_date');
        });
    }

    public function down(): void
    {
        Schema::table('church_profiles', function (Blueprint $table) {
            $table->dropIndex(['contact_origin']);
            $table->dropIndex(['first_contact_date']);

            $table->dropColumn([
                'contact_origin',
                'first_contact_date',
                'contact_origin_details',
            ]);
        });
    }
};
