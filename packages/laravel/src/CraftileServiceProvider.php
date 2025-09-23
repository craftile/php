<?php

declare(strict_types=1);

namespace Craftile\Laravel;

use Craftile\Laravel\View\BlockCacheManager;
use Craftile\Laravel\View\JsonViewCompiler;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\CompilerEngine;

class CraftileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/craftile.php', 'craftile');

        $this->app->singleton('craftile', Craftile::class);
        $this->app->singleton(BlockSchemaRegistry::class);
        $this->app->singleton(PropertyTransformerRegistry::class);
        $this->app->singleton(BlockDiscovery::class);
        $this->app->singleton(BlockFlattener::class);
        $this->app->singleton(BlockDatastore::class);
        $this->app->singleton(PreviewDataCollector::class);

        $this->registerJsonViewCompiler();
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'craftile');
    }

    /**
     * Register JSON/YAML view compiler.
     */
    protected function registerJsonViewCompiler()
    {
        $this->app->singleton(BlockCacheManager::class, function ($app) {
            return new BlockCacheManager($app['files']);
        });

        $this->app->singleton(JsonViewCompiler::class, function ($app) {
            return new JsonViewCompiler(
                $app['files'],
                $app['config']['view.compiled'],
                $app['blade.compiler'],
                $app[BlockCacheManager::class]
            );
        });

        $this->app->singleton('jsonview.compiler', function ($app) {
            return $app[JsonViewCompiler::class];
        });

        $this->app->extend('view.engine.resolver', function ($resolver) {
            $resolver->register('jsonview', function () {
                return new CompilerEngine($this->app['jsonview.compiler'], $this->app['files']);
            });

            return $resolver;
        });

        // Register view extensions
        $extensions = config('craftile.view_compiler.extensions', ['json', 'yml', 'yaml']);
        foreach ($extensions as $extension) {
            $this->app['view']->addExtension($extension, 'jsonview');
        }
    }
}
