<?php

declare(strict_types=1);

use Craftile\Laravel\DiscoveryManifest;
use Craftile\Laravel\DiscoveryRoots;
use Craftile\Laravel\Exceptions\InvalidDiscoveryRootException;
use Tests\Laravel\Stubs\Discovery\ContainerBlock;
use Tests\Laravel\Stubs\Discovery\Content\TextBlock;
use Tests\Laravel\Stubs\Discovery\DiscoveryBlock;
use Tests\Laravel\Stubs\Discovery\Presets\ContainerDiscoveryPreset;
use Tests\Laravel\Stubs\Discovery\StubTestBlock;

describe('DiscoveryManifest', function () {
    beforeEach(function () {
        app(DiscoveryManifest::class)->clear();
    });

    afterEach(function () {
        app(DiscoveryManifest::class)->clear();
    });

    it('builds a manifest from configured and declared roots', function () {
        config([
            'craftile.discovery.blocks' => [
                'Tests\\Laravel\\Stubs\\Discovery' => __DIR__.'/../Stubs/Discovery',
            ],
            'craftile.discovery.presets' => [],
        ]);

        app(DiscoveryRoots::class)->addPresetRoot(
            'Tests\\Laravel\\Stubs\\Discovery\\Presets',
            __DIR__.'/../Stubs/Discovery/Presets'
        );

        $manifest = app(DiscoveryManifest::class)->build();

        expect($manifest)
            ->toHaveKeys(['generated_at', 'roots', 'blocks', 'presets'])
            ->and(array_column($manifest['blocks'], 'class'))->toBe([
                ContainerBlock::class,
                TextBlock::class,
                DiscoveryBlock::class,
                StubTestBlock::class,
            ])
            ->and(array_column($manifest['presets'], 'class'))->toBe([
                ContainerDiscoveryPreset::class,
            ]);

        expect($manifest['roots']['blocks'])->toHaveCount(1)
            ->and($manifest['roots']['presets'])->toHaveCount(1);
    });

    it('writes loads and clears the cached manifest', function () {
        config([
            'craftile.discovery.blocks' => [
                'Tests\\Laravel\\Stubs\\Discovery' => __DIR__.'/../Stubs/Discovery',
            ],
        ]);

        $manifest = app(DiscoveryManifest::class);
        $cached = $manifest->cache();

        expect($manifest->exists())->toBeTrue()
            ->and($manifest->load())->toBe($cached)
            ->and($manifest->clear())->toBeTrue()
            ->and($manifest->exists())->toBeFalse();
    });

    it('caches and clears the manifest through artisan commands', function () {
        $manifest = app(DiscoveryManifest::class);

        $this->artisan('craftile:cache')
            ->assertExitCode(0);

        expect($manifest->exists())->toBeTrue();

        $this->artisan('craftile:clear')
            ->assertExitCode(0);

        expect($manifest->exists())->toBeFalse();
    });

    it('throws when a configured root is missing', function () {
        config([
            'craftile.discovery.blocks' => [
                'App\\MissingBlocks' => __DIR__.'/../Stubs/MissingBlocks',
            ],
        ]);

        expect(fn () => app(DiscoveryManifest::class)->build())
            ->toThrow(InvalidDiscoveryRootException::class);
    });

});
