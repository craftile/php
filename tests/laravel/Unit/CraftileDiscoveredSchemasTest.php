<?php

declare(strict_types=1);

use Craftile\Laravel\BlockSchemaRegistry;
use Craftile\Laravel\DiscoveryManifest;
use Craftile\Laravel\Facades\Craftile;
use Tests\Laravel\Stubs\Discovery\ContainerBlock;
use Tests\Laravel\Stubs\Discovery\StubTestBlock;

describe('Craftile discovered schemas', function () {
    beforeEach(function () {
        app(DiscoveryManifest::class)->clear();
    });

    it('does not register discovered schemas during service provider boot', function () {
        config([
            'craftile.discovery.blocks' => [
                'Tests\\Laravel\\Stubs\\Discovery' => __DIR__.'/../Stubs/Discovery',
            ],
        ]);

        expect(app(BlockSchemaRegistry::class)->getAllSchemas())->toBeEmpty();
    });

    it('registers discovered schemas and presets explicitly on every call', function () {
        Craftile::discoverBlocksIn('Tests\\Laravel\\Stubs\\Discovery', __DIR__.'/../Stubs/Discovery');
        Craftile::discoverPresetsIn('Tests\\Laravel\\Stubs\\Discovery\\Presets', __DIR__.'/../Stubs/Discovery/Presets');

        Craftile::registerDiscoveredSchemas();
        Craftile::registerDiscoveredSchemas();

        $registry = app(BlockSchemaRegistry::class);
        $container = $registry->get('container');

        expect($registry->hasSchema('stub-test-block'))->toBeTrue()
            ->and($container)->not->toBeNull()
            ->and($container->presets)->toHaveCount(2)
            ->and($container->presets[1]->toArray()['name'])->toBe('Container Discovery Preset');
    });

    it('uses a cached manifest as the authoritative source', function () {
        $manifest = app(DiscoveryManifest::class);
        app('files')->ensureDirectoryExists(dirname($manifest->path()));
        app('files')->put($manifest->path(), "<?php\n\nreturn ".var_export([
            'generated_at' => now('UTC')->toIso8601String(),
            'roots' => [
                'blocks' => [],
                'presets' => [],
            ],
            'blocks' => [
                [
                    'class' => StubTestBlock::class,
                    'path' => 'tests/laravel/Stubs/Discovery/StubTestBlock.php',
                    'namespace' => 'Tests\\Laravel\\Stubs\\Discovery',
                ],
            ],
            'presets' => [],
        ], true).";\n");

        Craftile::discoverBlocksIn('Tests\\Laravel\\Stubs\\Discovery', __DIR__.'/../Stubs/Discovery');
        Craftile::registerDiscoveredSchemas();

        $registry = app(BlockSchemaRegistry::class);

        expect($registry->hasSchema('stub-test-block'))->toBeTrue()
            ->and($registry->hasSchema(ContainerBlock::type()))->toBeFalse();
    });

    it('registers later duplicate block types last', function () {
        Craftile::discoverBlocksIn('Tests\\Laravel\\Stubs\\Discovery', __DIR__.'/../Stubs/Discovery');

        Craftile::registerBlock(ContainerBlock::class);
        Craftile::registerDiscoveredSchemas();

        expect(app(BlockSchemaRegistry::class)->get('container')->class)->toBe(ContainerBlock::class);
    });

    it('can filter discovered entries with a persistent callback', function () {
        Craftile::discoverBlocksIn('Tests\\Laravel\\Stubs\\Discovery', __DIR__.'/../Stubs/Discovery');

        Craftile::filterDiscoveredSchemasUsing(fn (array $entry, string $type) => $type === 'block'
            && $entry['class'] === StubTestBlock::class);

        Craftile::registerDiscoveredSchemas();

        $registry = app(BlockSchemaRegistry::class);

        expect($registry->hasSchema('stub-test-block'))->toBeTrue()
            ->and($registry->hasSchema(ContainerBlock::type()))->toBeFalse();
    });

    it('uses the per-call filter instead of the persistent callback', function () {
        Craftile::discoverBlocksIn('Tests\\Laravel\\Stubs\\Discovery', __DIR__.'/../Stubs/Discovery');

        Craftile::filterDiscoveredSchemasUsing(fn () => false);
        Craftile::registerDiscoveredSchemas(fn (array $entry, string $type) => $type === 'block'
            && $entry['class'] === StubTestBlock::class);

        $registry = app(BlockSchemaRegistry::class);

        expect($registry->hasSchema('stub-test-block'))->toBeTrue()
            ->and($registry->hasSchema(ContainerBlock::type()))->toBeFalse();
    });

    it('can clear the persistent callback with null', function () {
        Craftile::discoverBlocksIn('Tests\\Laravel\\Stubs\\Discovery', __DIR__.'/../Stubs/Discovery');

        Craftile::filterDiscoveredSchemasUsing(fn () => false);
        Craftile::filterDiscoveredSchemasUsing(null);
        Craftile::registerDiscoveredSchemas();

        expect(app(BlockSchemaRegistry::class)->hasSchema('stub-test-block'))->toBeTrue()
            ->and(app(BlockSchemaRegistry::class)->hasSchema(ContainerBlock::type()))->toBeTrue();
    });

    it('passes preset entries through the discovered schema filter', function () {
        Craftile::discoverBlocksIn('Tests\\Laravel\\Stubs\\Discovery', __DIR__.'/../Stubs/Discovery');
        Craftile::discoverPresetsIn('Tests\\Laravel\\Stubs\\Discovery\\Presets', __DIR__.'/../Stubs/Discovery/Presets');

        Craftile::registerDiscoveredSchemas(fn (array $entry, string $type) => $type === 'block');

        $container = app(BlockSchemaRegistry::class)->get('container');

        expect($container)->not->toBeNull()
            ->and($container->presets)->toHaveCount(1);
    });

    it('applies the current filter each time discovered schemas are registered', function () {
        Craftile::discoverBlocksIn('Tests\\Laravel\\Stubs\\Discovery', __DIR__.'/../Stubs/Discovery');

        Craftile::registerDiscoveredSchemas(fn (array $entry, string $type) => $type === 'block'
            && $entry['class'] === StubTestBlock::class);
        Craftile::registerDiscoveredSchemas(fn () => true);

        $registry = app(BlockSchemaRegistry::class);

        expect($registry->hasSchema('stub-test-block'))->toBeTrue()
            ->and($registry->hasSchema(ContainerBlock::type()))->toBeTrue();
    });
});
