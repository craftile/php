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
