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
