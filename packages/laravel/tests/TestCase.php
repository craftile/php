<?php

declare(strict_types=1);

namespace Craftile\Laravel\Tests;

use Craftile\Laravel\CraftileServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CraftileServiceProvider::class,
        ];
    }
}
