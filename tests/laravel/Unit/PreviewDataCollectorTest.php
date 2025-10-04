<?php

use Craftile\Laravel\BlockData;
use Craftile\Laravel\PreviewDataCollector;

test('can track regions', function () {
    $collector = app(PreviewDataCollector::class);

    $collector->startRegion('header');
    $collector->endRegion('header');

    $data = $collector->getCollectedData();

    expect($data['regions'])->toHaveCount(1);
    expect($data['regions'][0]['name'])->toBe('header');
    expect($data['regions'][0]['blocks'])->toBeArray();
});

test('can track blocks in regions', function () {
    $collector = app(PreviewDataCollector::class);

    $collector->startRegion('content');

    $blockData = BlockData::make([
        'id' => 'block-1',
        'type' => 'text',
        'properties' => ['content' => 'Hello'],
    ]);

    $collector->startBlock('block-1', $blockData);
    $collector->endBlock('block-1');
    $collector->endRegion('content');

    $data = $collector->getCollectedData();

    expect($data['regions'][0]['blocks'])->toHaveCount(1);
    expect($data['regions'][0]['blocks'][0])->toBe('block-1');
    expect($data['blocks']['block-1']['type'])->toBe('text');
    expect($data['blocks']['block-1']['properties']['content'])->toBe('Hello');
});

test('can track content layers', function () {
    $collector = app(PreviewDataCollector::class);

    $collector->startRegion('header');
    $collector->endRegion('header');

    $collector->beforeContent();
    $collector->startContent();

    $collector->startRegion('main');
    $collector->endRegion('main');

    $collector->endContent();
    $collector->afterContent();

    $collector->startRegion('footer');
    $collector->endRegion('footer');

    $data = $collector->getCollectedData();

    // Verify regions are in correct order
    expect($data['regions'])->toHaveCount(3);
    expect($data['regions'][0]['name'])->toBe('header');
    expect($data['regions'][1]['name'])->toBe('main');
    expect($data['regions'][2]['name'])->toBe('footer');
});

test('detects if currently collecting', function () {
    $collector = app(PreviewDataCollector::class);

    // Mock request without preview
    expect($collector->isCollecting())->toBeFalse();

    // Mock request with preview and clear Craftile cache
    request()->merge(['_preview' => 'true']);
    $craftile = app('craftile');
    $reflection = new ReflectionClass($craftile);
    $property = $reflection->getProperty('previewModeCache');
    $property->setAccessible(true);
    $property->setValue($craftile, null);

    expect($collector->isCollecting())->toBeTrue();
});

test('can check if in content region', function () {
    $collector = app(PreviewDataCollector::class);

    expect($collector->inContentRegion())->toBeFalse();

    $collector->startContent();
    expect($collector->inContentRegion())->toBeTrue();

    $collector->endContent();
    expect($collector->inContentRegion())->toBeFalse();
});

test('tracks render order of child blocks', function () {
    $collector = app(PreviewDataCollector::class);

    // Create parent block
    $parentBlock = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['dynamic-1', 'dynamic-2'],
    ]);

    $collector->startBlock('parent', $parentBlock);

    // Render static blocks first (as they appear in template)
    $staticBlock1 = BlockData::make([
        'id' => 'static-1',
        'type' => 'text',
        'properties' => ['content' => 'Static 1'],
        'parentId' => 'parent',
        'static' => true,
    ]);
    $collector->startBlock('static-1', $staticBlock1);

    $staticBlock2 = BlockData::make([
        'id' => 'static-2',
        'type' => 'text',
        'properties' => ['content' => 'Static 2'],
        'parentId' => 'parent',
        'static' => true,
    ]);
    $collector->startBlock('static-2', $staticBlock2);

    // Render dynamic blocks (from JSON children array)
    $dynamicBlock1 = BlockData::make([
        'id' => 'dynamic-1',
        'type' => 'button',
        'properties' => ['text' => 'Click me'],
        'parentId' => 'parent',
    ]);
    $collector->startBlock('dynamic-1', $dynamicBlock1);

    $dynamicBlock2 = BlockData::make([
        'id' => 'dynamic-2',
        'type' => 'button',
        'properties' => ['text' => 'Submit'],
        'parentId' => 'parent',
    ]);
    $collector->startBlock('dynamic-2', $dynamicBlock2);

    $data = $collector->getCollectedData();

    // Parent should have children in render order: static blocks first, then dynamic
    expect($data['blocks']['parent']['children'])->toBe([
        'static-1',
        'static-2',
        'dynamic-1',
        'dynamic-2',
    ]);
});

test('does not duplicate static blocks on repeated rendering', function () {
    $collector = app(PreviewDataCollector::class);

    $staticBlock = BlockData::make([
        'id' => 'static-block',
        'type' => 'text',
        'properties' => ['content' => 'Static content'],
        'static' => true,
    ]);

    // Render the same static block multiple times (simulating a loop)
    $collector->startBlock('static-block', $staticBlock);
    $collector->startBlock('static-block', $staticBlock);
    $collector->startBlock('static-block', $staticBlock);

    $data = $collector->getCollectedData();

    // Block should only be collected once
    expect($data['blocks'])->toHaveKey('static-block');
    expect($data['blocks']['static-block']['type'])->toBe('text');
});

test('tracks children in visual order for nested parents', function () {
    $collector = app(PreviewDataCollector::class);

    // Create grandparent
    $grandparent = BlockData::make([
        'id' => 'grandparent',
        'type' => 'container',
        'properties' => [],
        'children' => ['parent'],
    ]);
    $collector->startBlock('grandparent', $grandparent);

    // Create parent with dynamic children in JSON
    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'feature',
        'properties' => [],
        'parentId' => 'grandparent',
        'children' => ['child-2'],
    ]);
    $collector->startBlock('parent', $parent);

    // Render static child first
    $child1 = BlockData::make([
        'id' => 'child-1',
        'type' => 'text',
        'properties' => [],
        'parentId' => 'parent',
        'static' => true,
    ]);
    $collector->startBlock('child-1', $child1);

    // Render dynamic child second
    $child2 = BlockData::make([
        'id' => 'child-2',
        'type' => 'button',
        'properties' => [],
        'parentId' => 'parent',
    ]);
    $collector->startBlock('child-2', $child2);

    $data = $collector->getCollectedData();

    // Parent should have children in visual order
    expect($data['blocks']['parent']['children'])->toBe(['child-1', 'child-2']);

    // Grandparent should still reference parent
    expect($data['blocks']['grandparent']['children'])->toBe(['parent']);
});
