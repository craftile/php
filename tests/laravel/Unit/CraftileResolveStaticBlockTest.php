<?php

use Craftile\Laravel\BlockDatastore;
use Craftile\Laravel\Craftile;

test('resolveStaticBlockId finds static block by semanticId', function () {
    $datastore = app(BlockDatastore::class);
    $craftile = app(Craftile::class);

    // Load blocks into datastore
    $datastore->loadFile(__DIR__.'/fixtures/static-blocks.json');

    // Resolve static block by parent and semantic ID
    $resolvedId = $craftile->resolveStaticBlockId('parent-block', 'child-static');

    expect($resolvedId)->toBe('child-static');
});

test('resolveStaticBlockId falls back to id when semanticId is null', function () {
    $datastore = app(BlockDatastore::class);
    $craftile = app(Craftile::class);

    // Load blocks into datastore
    $datastore->loadFile(__DIR__.'/fixtures/static-blocks-no-semantic.json');

    // Should still find block by matching id when semanticId is null
    $resolvedId = $craftile->resolveStaticBlockId('parent-block', 'child-static');

    expect($resolvedId)->toBe('child-static');
});

test('resolveStaticBlockId returns null when block not found', function () {
    $datastore = app(BlockDatastore::class);
    $craftile = app(Craftile::class);

    // Load blocks into datastore
    $datastore->loadFile(__DIR__.'/fixtures/static-blocks.json');

    // Try to resolve non-existent block
    $resolvedId = $craftile->resolveStaticBlockId('parent-block', 'non-existent');

    expect($resolvedId)->toBeNull();
});

test('resolveStaticBlockId only matches blocks with correct parent', function () {
    $datastore = app(BlockDatastore::class);
    $craftile = app(Craftile::class);

    // Load blocks into datastore
    $datastore->loadFile(__DIR__.'/fixtures/static-blocks-multiple-parents.json');

    // Should only find block with matching parent
    $resolvedId = $craftile->resolveStaticBlockId('parent-1', 'shared-name');

    expect($resolvedId)->toBe('parent-1-shared');

    $resolvedId2 = $craftile->resolveStaticBlockId('parent-2', 'shared-name');

    expect($resolvedId2)->toBe('parent-2-shared');
});

test('resolveStaticBlockId only matches static blocks', function () {
    $datastore = app(BlockDatastore::class);
    $craftile = app(Craftile::class);

    // Load blocks with both static and dynamic children
    $datastore->loadFile(__DIR__.'/fixtures/static-and-dynamic-blocks.json');

    // Should not match dynamic block with same semantic ID
    $resolvedId = $craftile->resolveStaticBlockId('parent-block', 'dynamic-child');

    expect($resolvedId)->toBeNull();
});
