<?php

declare(strict_types=1);

namespace Craftile\Laravel;

use Craftile\Laravel\BlockSchemaRegistry;
use Illuminate\Support\ServiceProvider;

class CraftileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BlockSchemaRegistry::class);
    }

    public function boot(): void
    {
        //
    }
}
