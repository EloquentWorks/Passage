<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Tests;

use EloquentWorks\Passage\Definitions\PassageRegistry;
use EloquentWorks\Passage\PassageServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PassageServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        app(PassageRegistry::class)->flush();
        parent::tearDown();
    }
}
