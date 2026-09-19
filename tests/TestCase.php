<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $this->prepareTestingEnvironment();

        $app = parent::createApplication();

        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTestingDatabaseIsIsolated();
    }

    private function prepareTestingEnvironment(): void
    {
        $variables = [
            'APP_ENV' => 'testing',
            'APP_KEY' => 'base64:2fl+KtvkblAZKa/NjXIK2hw5HkF5xEmkyzehBV3VIj4=',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
            'APP_CONFIG_CACHE' => Application::inferBasePath().'/bootstrap/cache/testing-config.php',
        ];

        foreach ($variables as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function assertTestingDatabaseIsIsolated(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        $default = config('database.default');
        $database = config('database.connections.sqlite.database');

        if ($default !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                'Tests must use sqlite :memory: so they do not wipe your development database. '
                ."Current connection is [{$default}] with sqlite database [{$database}]. "
                .'Run `php artisan config:clear` if you recently cached config for production.'
            );
        }
    }
}
