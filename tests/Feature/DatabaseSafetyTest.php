<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSafetyTest extends TestCase
{
    public function test_phpunit_uses_only_sqlite_memory_database(): void
    {
        $connection = DB::connection();

        $this->assertSame(
            'testing',
            app()->environment()
        );

        $this->assertSame(
            'sqlite',
            DB::getDefaultConnection()
        );

        $this->assertSame(
            'sqlite',
            $connection->getDriverName()
        );

        $this->assertSame(
            ':memory:',
            $connection->getDatabaseName()
        );
    }
}
