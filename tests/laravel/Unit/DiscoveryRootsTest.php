<?php

declare(strict_types=1);

use Craftile\Laravel\DiscoveryRoots;
use Craftile\Laravel\Exceptions\InvalidDiscoveryRootException;

describe('DiscoveryRoots', function () {
    it('merges config roots before declared roots and dedupes exact pairs', function () {
        config([
            'craftile.discovery.enabled' => true,
            'craftile.discovery.blocks' => [
                'App\\Blocks' => 'app/Blocks',
                [
                    'namespace' => 'App\\OtherBlocks',
                    'path' => 'app/OtherBlocks',
                ],
            ],
        ]);

        $roots = app(DiscoveryRoots::class);
        $roots->addBlockRoot('Vendor\\Blocks\\', base_path('vendor/package/src/Blocks/'));
        $roots->addBlockRoot('Vendor\\Blocks', base_path('vendor/package/src/Blocks'));

        expect($roots->blocks())->toBe([
            [
                'namespace' => 'App\\Blocks',
                'path' => base_path('app/Blocks'),
            ],
            [
                'namespace' => 'App\\OtherBlocks',
                'path' => base_path('app/OtherBlocks'),
            ],
            [
                'namespace' => 'Vendor\\Blocks',
                'path' => base_path('vendor/package/src/Blocks'),
            ],
        ]);
    });

    it('ignores config roots when discovery is disabled but keeps declared roots', function () {
        config([
            'craftile.discovery.enabled' => false,
            'craftile.discovery.blocks' => [
                'App\\Blocks' => 'app/Blocks',
            ],
        ]);

        $roots = app(DiscoveryRoots::class);
        $roots->addBlockRoot('Vendor\\Blocks', 'vendor/package/src/Blocks');

        expect($roots->blocks())->toBe([
            [
                'namespace' => 'Vendor\\Blocks',
                'path' => base_path('vendor/package/src/Blocks'),
            ],
        ]);
    });

    it('rejects malformed roots', function () {
        config([
            'craftile.discovery.blocks' => [
                ['namespace' => 'App\\Blocks'],
            ],
        ]);

        expect(fn () => app(DiscoveryRoots::class)->blocks())
            ->toThrow(InvalidDiscoveryRootException::class);
    });

    it('rejects empty namespaces', function () {
        expect(fn () => app(DiscoveryRoots::class)->addBlockRoot('', 'app/Blocks'))
            ->toThrow(InvalidDiscoveryRootException::class);
    });
});
