<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        /*
         * PHPUnit must never inherit the production database
         * connection or production config cache.
         *
         * These values are installed before Laravel boots so even
         * Docker-level DB_* environment variables cannot point a test
         * process at MariaDB.
         */
        $configCache =
            sys_get_temp_dir()
            . '/coqp-phpunit-config-'
            . getmypid()
            . '.php';

        $testingEnvironment = [
            'APP_ENV' => 'testing',
            'APP_CONFIG_CACHE' => $configCache,
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
        ];

        foreach (
            $testingEnvironment
            as $key => $value
        ) {
            putenv($key . '=' . $value);

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        /*
         * Never reuse a stale PHPUnit config cache from an earlier
         * process.
         */
        if (is_file($configCache)) {
            @unlink($configCache);
        }

        $app = parent::createApplication();

        /*
         * Fail closed before testing traits such as RefreshDatabase
         * are invoked.
         */
        $connectionName = (string) $app['config']->get(
            'database.default'
        );

        $connection =
            $app['db']->connection($connectionName);

        $driver =
            $connection->getDriverName();

        $database =
            (string) $connection->getDatabaseName();

        if (
            ! $app->environment('testing')
            || $connectionName !== 'sqlite'
            || $driver !== 'sqlite'
            || $database !== ':memory:'
        ) {
            throw new RuntimeException(
                'Unsafe PHPUnit database configuration refused. '
                . 'Environment: '
                . $app->environment()
                . '; connection: '
                . $connectionName
                . '; driver: '
                . $driver
                . '; database: '
                . $database
            );
        }

        return $app;
    }
}
