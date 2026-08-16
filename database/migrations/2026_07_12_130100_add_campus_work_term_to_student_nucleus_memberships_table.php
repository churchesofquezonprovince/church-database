<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $database = DB::connection()->getDatabaseName();

        $indexExists = function (string $indexName) use ($database): bool {
            return DB::table('information_schema.statistics')
                ->where('table_schema', $database)
                ->where('table_name', 'student_nucleus_memberships')
                ->where('index_name', $indexName)
                ->exists();
        };

        $constraintExists = function (string $constraintName) use ($database): bool {
            return DB::table('information_schema.table_constraints')
                ->where('constraint_schema', $database)
                ->where('table_name', 'student_nucleus_memberships')
                ->where('constraint_name', $constraintName)
                ->exists();
        };

        /*
         * Add campus_work_term_id only if the failed migration
         * did not already create it.
         */
        if (! Schema::hasColumn(
            'student_nucleus_memberships',
            'campus_work_term_id'
        )) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->unsignedInteger('campus_work_term_id')
                        ->nullable()
                        ->after('id');
                }
            );
        }

        /*
         * Find or create the initial academic term.
         */
        $termId = DB::table('campus_work_terms')
            ->where('academic_year', '2026-2027')
            ->where('semester', '1st Semester')
            ->value('id');

        if (! $termId) {
            $termId = DB::table('campus_work_terms')->insertGetId([
                'academic_year' => '2026-2027',
                'semester' => '1st Semester',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        /*
         * Preserve all existing Student Nucleus members by assigning
         * them to the initial term.
         */
        DB::table('student_nucleus_memberships')
            ->whereNull('campus_work_term_id')
            ->update([
                'campus_work_term_id' => $termId,
            ]);

        /*
         * The existing unique person_id index is currently supporting
         * the person_id foreign key. Add a regular index first so MySQL
         * can safely allow the old unique index to be removed.
         */
        if (! $indexExists('snm_person_id_index')) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->index(
                        'person_id',
                        'snm_person_id_index'
                    );
                }
            );
        }

        /*
         * Remove the old rule that allowed one membership per person
         * across the entire database.
         */
        if ($indexExists('student_nucleus_memberships_person_id_unique')) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->dropUnique(
                        'student_nucleus_memberships_person_id_unique'
                    );
                }
            );
        }

        /*
         * All existing records now have a term, so make the column required.
         */
        DB::statement(
            'ALTER TABLE `student_nucleus_memberships`
             MODIFY `campus_work_term_id` INT UNSIGNED NOT NULL'
        );

        /*
         * Add term foreign key.
         */
        if (! $constraintExists('snm_term_fk')) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->foreign(
                        'campus_work_term_id',
                        'snm_term_fk'
                    )
                        ->references('id')
                        ->on('campus_work_terms')
                        ->cascadeOnDelete();
                }
            );
        }

        /*
         * A person may appear once per academic term.
         */
        if (! $indexExists('snm_term_person_unique')) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->unique(
                        [
                            'campus_work_term_id',
                            'person_id',
                        ],
                        'snm_term_person_unique'
                    );
                }
            );
        }
    }

    public function down(): void
    {
        $database = DB::connection()->getDatabaseName();

        $indexExists = function (string $indexName) use ($database): bool {
            return DB::table('information_schema.statistics')
                ->where('table_schema', $database)
                ->where('table_name', 'student_nucleus_memberships')
                ->where('index_name', $indexName)
                ->exists();
        };

        $constraintExists = function (string $constraintName) use ($database): bool {
            return DB::table('information_schema.table_constraints')
                ->where('constraint_schema', $database)
                ->where('table_name', 'student_nucleus_memberships')
                ->where('constraint_name', $constraintName)
                ->exists();
        };

        if ($indexExists('snm_term_person_unique')) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->dropUnique('snm_term_person_unique');
                }
            );
        }

        if ($constraintExists('snm_term_fk')) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->dropForeign('snm_term_fk');
                }
            );
        }

        if (Schema::hasColumn(
            'student_nucleus_memberships',
            'campus_work_term_id'
        )) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->dropColumn('campus_work_term_id');
                }
            );
        }

        /*
         * Restore the original one-membership-per-person rule.
         */
        if (! $indexExists('student_nucleus_memberships_person_id_unique')) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->unique('person_id');
                }
            );
        }

        /*
         * The restored unique index can now support the person foreign key.
         */
        if ($indexExists('snm_person_id_index')) {
            Schema::table(
                'student_nucleus_memberships',
                function (Blueprint $table): void {
                    $table->dropIndex('snm_person_id_index');
                }
            );
        }
    }
};
