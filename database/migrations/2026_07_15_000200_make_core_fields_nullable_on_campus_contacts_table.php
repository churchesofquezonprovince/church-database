<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE campus_contacts
             MODIFY firstname VARCHAR(100) NULL'
        );

        DB::statement(
            'ALTER TABLE campus_contacts
             MODIFY lastname VARCHAR(100) NULL'
        );

        DB::statement(
            "ALTER TABLE campus_contacts
             MODIFY sex ENUM('Male', 'Female') NULL"
        );

        DB::statement(
            'ALTER TABLE campus_contacts
             MODIFY locality VARCHAR(150) NULL'
        );
    }

    public function down(): void
    {
        $hasIncompleteContacts = DB::table('campus_contacts')
            ->whereNull('firstname')
            ->orWhereNull('lastname')
            ->orWhereNull('sex')
            ->orWhereNull('locality')
            ->exists();

        if ($hasIncompleteContacts) {
            throw new RuntimeException(
                'Cannot rollback because incomplete Campus Contacts exist.'
            );
        }

        DB::statement(
            'ALTER TABLE campus_contacts
             MODIFY firstname VARCHAR(100) NOT NULL'
        );

        DB::statement(
            'ALTER TABLE campus_contacts
             MODIFY lastname VARCHAR(100) NOT NULL'
        );

        DB::statement(
            "ALTER TABLE campus_contacts
             MODIFY sex ENUM('Male', 'Female') NOT NULL"
        );

        DB::statement(
            'ALTER TABLE campus_contacts
             MODIFY locality VARCHAR(150) NOT NULL'
        );
    }
};
