<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_hymn_requests',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                $table->foreign(
                    'shepherding_contact_id',
                    'sch_hymn_req_contact_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->unsignedBigInteger(
                    'hymn_addition_request_id'
                );

                $table->foreign(
                    'hymn_addition_request_id',
                    'sch_hymn_req_request_fk'
                )
                    ->references('id')
                    ->on('hymn_addition_requests')
                    ->cascadeOnDelete();

                $table->unsignedSmallInteger(
                    'sort_order'
                )->default(1);

                $table->timestamps();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'hymn_addition_request_id',
                    ],
                    'shepherding_hymn_requests_unique'
                );

                $table->index(
                    [
                        'hymn_addition_request_id',
                        'shepherding_contact_id',
                    ],
                    'shepherding_hymn_requests_request_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_hymn_requests'
        );
    }
};
