<?php

use Craftile\Laravel\Craftile;

it('resolves region view with default prefix', function () {
    $craftile = app(Craftile::class);

    $result = $craftile->resolveRegionView('header');

    expect($result)->toBe('regions.header');
});

it('resolves region view with custom prefix from config', function () {
    config(['craftile.region_view_prefix' => 'components']);

    $craftile = app(Craftile::class);

    $result = $craftile->resolveRegionView('header');

    expect($result)->toBe('components.header');
});

it('resolves region view with custom resolver', function () {
    $craftile = app(Craftile::class);

    $craftile->resolveRegionViewUsing(function ($regionName) {
        return "custom.{$regionName}";
    });

    $result = $craftile->resolveRegionView('header');

    expect($result)->toBe('custom.header');
});

it('custom resolver takes precedence over config', function () {
    config(['craftile.region_view_prefix' => 'components']);

    $craftile = app(Craftile::class);

    $craftile->resolveRegionViewUsing(function ($regionName) {
        return "override.{$regionName}";
    });

    $result = $craftile->resolveRegionView('header');

    expect($result)->toBe('override.header');
});

it('can return complex view paths with custom resolver', function () {
    $craftile = app(Craftile::class);

    $craftile->resolveRegionViewUsing(function ($regionName) {
        return "themes.default.regions.{$regionName}";
    });

    $result = $craftile->resolveRegionView('sidebar');

    expect($result)->toBe('themes.default.regions.sidebar');
});

test('shouldRenderBlock returns true for enabled blocks by default', function () {
    $craftile = app(Craftile::class);
    $blockData = \Craftile\Laravel\BlockData::make([
        'id' => 'test-block',
        'type' => 'test',
        'disabled' => false,
    ]);

    expect($craftile->shouldRenderBlock($blockData))->toBeTrue();
});

test('shouldRenderBlock returns false for disabled blocks by default', function () {
    $craftile = app(Craftile::class);
    $blockData = \Craftile\Laravel\BlockData::make([
        'id' => 'test-block',
        'type' => 'test',
        'disabled' => true,
    ]);

    expect($craftile->shouldRenderBlock($blockData))->toBeFalse();
});

test('shouldRenderBlock can use custom checker', function () {
    $craftile = app(Craftile::class);

    // Custom logic: only render blocks with 'public' in their ID
    $craftile->checkIfBlockCanRenderUsing(function ($blockData) {
        return str_contains($blockData->id, 'public');
    });

    $publicBlock = \Craftile\Laravel\BlockData::make([
        'id' => 'public-block',
        'type' => 'test',
    ]);

    $privateBlock = \Craftile\Laravel\BlockData::make([
        'id' => 'private-block',
        'type' => 'test',
    ]);

    expect($craftile->shouldRenderBlock($publicBlock))->toBeTrue();
    expect($craftile->shouldRenderBlock($privateBlock))->toBeFalse();
});

test('custom render checker overrides default disabled logic', function () {
    $craftile = app(Craftile::class);

    // Custom logic that ignores disabled flag and only checks type
    $craftile->checkIfBlockCanRenderUsing(function ($blockData) {
        return $blockData->type === 'always-render';
    });

    $disabledButAllowedType = \Craftile\Laravel\BlockData::make([
        'id' => 'test-block',
        'type' => 'always-render',
        'disabled' => true, // This should be ignored
    ]);

    $enabledButWrongType = \Craftile\Laravel\BlockData::make([
        'id' => 'test-block',
        'type' => 'never-render',
        'disabled' => false,
    ]);

    expect($craftile->shouldRenderBlock($disabledButAllowedType))->toBeTrue();
    expect($craftile->shouldRenderBlock($enabledButWrongType))->toBeFalse();
});

test('createBlockData uses default BlockData class', function () {
    $craftile = app(Craftile::class);

    $blockData = $craftile->createBlockData([
        'id' => 'test-block',
        'type' => 'test',
    ]);

    expect($blockData)->toBeInstanceOf(\Craftile\Laravel\BlockData::class);
    expect($blockData->id)->toBe('test-block');
    expect($blockData->type)->toBe('test');
});

test('createBlockData uses factory when provided', function () {
    $craftile = app(Craftile::class);

    // Create a custom factory that adds a prefix to the ID
    $craftile->createBlockDataUsing(function ($blockData, $resolveChildData) {
        $blockData['id'] = 'custom-'.$blockData['id'];

        return \Craftile\Laravel\BlockData::make($blockData, $resolveChildData);
    });

    $blockData = $craftile->createBlockData([
        'id' => 'test-block',
        'type' => 'test',
    ]);

    expect($blockData->id)->toBe('custom-test-block');
});

test('createBlockData validates custom class extends BlockData', function () {
    $craftile = app(Craftile::class);

    // Mock config to return invalid class
    config(['craftile.block_data_class' => \stdClass::class]);

    expect(fn () => $craftile->createBlockData(['id' => 'test', 'type' => 'test']))
        ->toThrow(\InvalidArgumentException::class, 'BlockData class \'stdClass\' must extend Craftile\Laravel\BlockData');
});

describe('filterContext', function () {
    it('filters out variables starting with __', function () {
        $craftile = app(Craftile::class);

        $vars = [
            'title' => 'Hello',
            '__env' => 'test-env',
            '__obLevel' => 1,
            'content' => 'World',
        ];

        $filtered = $craftile->filterContext($vars);

        expect($filtered)->toHaveKey('title');
        expect($filtered)->toHaveKey('content');
        expect($filtered)->not->toHaveKey('__env');
        expect($filtered)->not->toHaveKey('__obLevel');
    });

    it('preserves __staticBlocksChildren variable', function () {
        $craftile = app(Craftile::class);

        $vars = [
            'title' => 'Hello',
            '__staticBlocksChildren' => ['child1', 'child2'],
            '__env' => 'test-env',
        ];

        $filtered = $craftile->filterContext($vars);

        expect($filtered)->toHaveKey('title');
        expect($filtered)->toHaveKey('__staticBlocksChildren');
        expect($filtered)->not->toHaveKey('__env');
    });

    it('filters out Laravel auto-injected variables', function () {
        $craftile = app(Craftile::class);

        $vars = [
            'title' => 'Hello',
            'app' => app(),
            'errors' => collect(),
            'content' => 'World',
        ];

        $filtered = $craftile->filterContext($vars);

        expect($filtered)->toHaveKey('title');
        expect($filtered)->toHaveKey('content');
        expect($filtered)->not->toHaveKey('app');
        expect($filtered)->not->toHaveKey('errors');
    });

    it('merges custom attributes', function () {
        $craftile = app(Craftile::class);

        $vars = [
            'title' => 'Hello',
            'content' => 'World',
        ];

        $customAttributes = [
            'class' => 'custom-class',
            'id' => 'custom-id',
        ];

        $filtered = $craftile->filterContext($vars, $customAttributes);

        expect($filtered)->toHaveKey('title');
        expect($filtered)->toHaveKey('content');
        expect($filtered)->toHaveKey('class');
        expect($filtered)->toHaveKey('id');
        expect($filtered['class'])->toBe('custom-class');
        expect($filtered['id'])->toBe('custom-id');
    });

    it('custom attributes override existing variables', function () {
        $craftile = app(Craftile::class);

        $vars = [
            'title' => 'Original',
            'content' => 'World',
        ];

        $customAttributes = [
            'title' => 'Overridden',
        ];

        $filtered = $craftile->filterContext($vars, $customAttributes);

        expect($filtered['title'])->toBe('Overridden');
        expect($filtered['content'])->toBe('World');
    });

    it('handles empty variables', function () {
        $craftile = app(Craftile::class);

        $filtered = $craftile->filterContext([]);

        expect($filtered)->toBeArray();
        expect($filtered)->toBeEmpty();
    });

    it('handles only custom attributes', function () {
        $craftile = app(Craftile::class);

        $customAttributes = [
            'custom1' => 'value1',
            'custom2' => 'value2',
        ];

        $filtered = $craftile->filterContext([], $customAttributes);

        expect($filtered)->toHaveKey('custom1');
        expect($filtered)->toHaveKey('custom2');
    });
});
