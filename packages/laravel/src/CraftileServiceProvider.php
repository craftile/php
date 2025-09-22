<?php

declare(strict_types=1);

namespace Craftile\Laravel;

use Illuminate\Support\ServiceProvider;

class CraftileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/craftile.php', 'craftile');

        $this->app->singleton('craftile', Craftile::class);
        $this->app->singleton(BlockSchemaRegistry::class);
    }

    public function boot(): void
    {
        //
    }
}
