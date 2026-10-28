<?php

use Craftile\Laravel\BlockData;
use Craftile\Laravel\Facades\BlockDatastore;
use Craftile\Laravel\Facades\Craftile;
use Craftile\Laravel\PreviewDataCollector;
use Craftile\Laravel\PreviewModeCache;

test('can track regions', function () {
    $collector = app(PreviewDataCollector::class);

    $collector->startRegion('header');
    $collector->endRegion('header');

    $data = $collector->getCollectedData();

    expect($data['regions'])->toHaveCount(1);
    expect($data['regions'][0]['id'])->toBe('header');
    expect($data['regions'][0]['name'])->toBe('header');
    expect($data['regions'][0]['blocks'])->toBeArray();
});

test('can track region id with display name', function () {
    $collector = app(PreviewDataCollector::class);

    $collector->startRegion('hero', 'Hero');
    $collector->endRegion('hero');

    $data = $collector->getCollectedData();

    expect($data['regions'])->toHaveCount(1);
    expect($data['regions'][0]['id'])->toBe('hero');
    expect($data['regions'][0]['name'])->toBe('Hero');
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

    expect($data['regions'][0]['id'])->toBe('content');
    expect($data['regions'][0]['blocks'])->toHaveCount(1);
    expect($data['regions'][0]['blocks'][0])->toBe('block-1');
    expect($data['blocks']['block-1']['type'])->toBe('text');
    expect($data['blocks']['block-1']['properties']['content'])->toBe('Hello');
});

test('tracks blocks under region id when display name differs', function () {
    $collector = app(PreviewDataCollector::class);

    $collector->startRegion('hero', 'Hero');

    $blockData = BlockData::make([
        'id' => 'block-1',
        'type' => 'text',
        'properties' => ['content' => 'Hello'],
    ]);

    $collector->startBlock('block-1', $blockData);
    $collector->endBlock('block-1');
    $collector->endRegion('hero');

    $data = $collector->getCollectedData();

    expect($data['regions'])->toHaveCount(1);
    expect($data['regions'][0]['id'])->toBe('hero');
    expect($data['regions'][0]['name'])->toBe('Hero');
    expect($data['regions'][0]['blocks'])->toBe(['block-1']);
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
    expect($data['regions'][0]['id'])->toBe('header');
    expect($data['regions'][0]['name'])->toBe('header');
    expect($data['regions'][1]['id'])->toBe('main');
    expect($data['regions'][1]['name'])->toBe('main');
    expect($data['regions'][2]['id'])->toBe('footer');
    expect($data['regions'][2]['name'])->toBe('footer');
});

test('orders regions by id when display names differ', function () {
    $collector = app(PreviewDataCollector::class);

    $collector->startRegion('header', 'Header');
    $collector->endRegion('header');

    $collector->beforeContent();
    $collector->startContent();

    $collector->startRegion('main', 'Main');
    $collector->endRegion('main');

    $collector->endContent();
    $collector->afterContent();

    $collector->startRegion('footer', 'Footer');
    $collector->endRegion('footer');

    $data = $collector->getCollectedData();

    expect(array_column($data['regions'], 'id'))->toBe(['header', 'main', 'footer']);
    expect(array_column($data['regions'], 'name'))->toBe(['Header', 'Main', 'Footer']);
});

test('detects if currently collecting', function () {
    $collector = app(PreviewDataCollector::class);

    // Mock request without preview
    expect($collector->isCollecting())->toBeFalse();

    // Mock request with preview and clear Craftile cache
    request()->merge(['_preview' => 'true']);
    app(PreviewModeCache::class)->forget();

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

test('groups kept unrendered static blocks with rendered statics', function () {
    $collector = app(PreviewDataCollector::class);

    // Create parent with children including an unrendered static block
    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['static-1', 'static-2', 'dynamic-1'],
    ]);
    $collector->startBlock('parent', $parent);

    // Render static-1 (static-2 not rendered due to conditional)
    $static1 = BlockData::make([
        'id' => 'static-1',
        'type' => 'text',
        'properties' => ['content' => 'Static 1'],
        'parentId' => 'parent',
        'static' => true,
    ]);
    $collector->startBlock('static-1', $static1);

    // Render dynamic-1
    $dynamic1 = BlockData::make([
        'id' => 'dynamic-1',
        'type' => 'button',
        'properties' => ['text' => 'Click'],
        'parentId' => 'parent',
    ]);
    $collector->startBlock('dynamic-1', $dynamic1);

    // Load static-2 into BlockDatastore
    $static2 = BlockData::make([
        'id' => 'static-2',
        'type' => 'text',
        'properties' => ['content' => 'Static 2'],
        'parentId' => 'parent',
        'static' => true,
        'keepInTree' => true,
    ]);
    BlockDatastore::shouldReceive('getBlock')
        ->with('static-2')
        ->andReturn($static2);

    $data = $collector->getCollectedData();

    // Expected: [static-1, static-2, dynamic-1]
    // static-2 should be grouped with static-1, before dynamic-1
    expect($data['blocks']['parent']['children'])->toBe(['static-1', 'static-2', 'dynamic-1']);
    expect($data['blocks'])->toHaveKey('static-2');
});

test('places kept unrendered static at end when no static rendered', function () {
    $collector = app(PreviewDataCollector::class);

    // Create parent with static and dynamic children
    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['static-1', 'dynamic-1', 'dynamic-2'],
    ]);
    $collector->startBlock('parent', $parent);

    // Only render dynamic blocks (static-1 in false conditional)
    $dynamic1 = BlockData::make([
        'id' => 'dynamic-1',
        'type' => 'button',
        'properties' => ['text' => 'Button 1'],
        'parentId' => 'parent',
    ]);
    $collector->startBlock('dynamic-1', $dynamic1);

    $dynamic2 = BlockData::make([
        'id' => 'dynamic-2',
        'type' => 'button',
        'properties' => ['text' => 'Button 2'],
        'parentId' => 'parent',
    ]);
    $collector->startBlock('dynamic-2', $dynamic2);

    // Load static-1 into BlockDatastore
    $static1 = BlockData::make([
        'id' => 'static-1',
        'type' => 'text',
        'properties' => ['content' => 'Static'],
        'parentId' => 'parent',
        'static' => true,
        'keepInTree' => true,
    ]);
    BlockDatastore::shouldReceive('getBlock')
        ->with('static-1')
        ->andReturn($static1);

    $data = $collector->getCollectedData();

    // Expected: [dynamic-1, dynamic-2, static-1]
    // static-1 should be at the end since no static blocks were rendered
    expect($data['blocks']['parent']['children'])->toBe(['dynamic-1', 'dynamic-2', 'static-1']);
    expect($data['blocks'])->toHaveKey('static-1');
});

test('collects multiple kept unrendered static blocks', function () {
    $collector = app(PreviewDataCollector::class);

    // Create parent with mix of rendered and unrendered statics
    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['static-1', 'static-2', 'static-3', 'dynamic-1'],
    ]);
    $collector->startBlock('parent', $parent);

    // Only render static-1 and dynamic-1 (static-2 and static-3 in conditionals)
    $static1 = BlockData::make([
        'id' => 'static-1',
        'type' => 'text',
        'properties' => [],
        'parentId' => 'parent',
        'static' => true,
    ]);
    $collector->startBlock('static-1', $static1);

    $dynamic1 = BlockData::make([
        'id' => 'dynamic-1',
        'type' => 'button',
        'properties' => [],
        'parentId' => 'parent',
    ]);
    $collector->startBlock('dynamic-1', $dynamic1);

    // Load unrendered statics into BlockDatastore
    BlockDatastore::shouldReceive('getBlock')
        ->with('static-2')
        ->andReturn(BlockData::make([
            'id' => 'static-2',
            'type' => 'text',
            'static' => true,
            'keepInTree' => true,
            'parentId' => 'parent',
        ]));

    BlockDatastore::shouldReceive('getBlock')
        ->with('static-3')
        ->andReturn(BlockData::make([
            'id' => 'static-3',
            'type' => 'text',
            'static' => true,
            'keepInTree' => true,
            'parentId' => 'parent',
        ]));

    $data = $collector->getCollectedData();

    // Expected: [static-1, static-2, static-3, dynamic-1]
    // Both unrendered statics should be grouped after static-1
    expect($data['blocks']['parent']['children'])->toBe(['static-1', 'static-2', 'static-3', 'dynamic-1']);
    expect($data['blocks'])->toHaveKey('static-2');
    expect($data['blocks'])->toHaveKey('static-3');
});

test('collects dynamic children of a disabled parent', function () {
    $collector = app(PreviewDataCollector::class);

    // Disabled parent: its body never renders, so children never call startBlock.
    // Only the parent itself is tracked (startBlock fires before the disabled guard).
    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['dynamic-1', 'dynamic-2'],
        'disabled' => true,
    ]);
    $collector->startBlock('parent', $parent);

    // Children exist in the datastore (from JSON) but were never rendered.
    BlockDatastore::shouldReceive('getBlock')
        ->with('dynamic-1')
        ->andReturn(BlockData::make([
            'id' => 'dynamic-1',
            'type' => 'button',
            'parentId' => 'parent',
        ]));

    BlockDatastore::shouldReceive('getBlock')
        ->with('dynamic-2')
        ->andReturn(BlockData::make([
            'id' => 'dynamic-2',
            'type' => 'button',
            'parentId' => 'parent',
        ]));

    $data = $collector->getCollectedData();

    // The complete tree must be present even though the parent is disabled.
    expect($data['blocks'])->toHaveKey('dynamic-1');
    expect($data['blocks'])->toHaveKey('dynamic-2');
    expect($data['blocks']['parent']['children'])->toBe(['dynamic-1', 'dynamic-2']);
});

test('collects nested dynamic subtree of a disabled parent', function () {
    $collector = app(PreviewDataCollector::class);

    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['child'],
        'disabled' => true,
    ]);
    $collector->startBlock('parent', $parent);

    BlockDatastore::shouldReceive('getBlock')
        ->with('child')
        ->andReturn(BlockData::make([
            'id' => 'child',
            'type' => 'container',
            'parentId' => 'parent',
            'children' => ['grandchild'],
        ]));

    BlockDatastore::shouldReceive('getBlock')
        ->with('grandchild')
        ->andReturn(BlockData::make([
            'id' => 'grandchild',
            'type' => 'text',
            'parentId' => 'child',
        ]));

    $data = $collector->getCollectedData();

    expect($data['blocks'])->toHaveKey('child');
    expect($data['blocks'])->toHaveKey('grandchild');
    expect($data['blocks']['child']['children'])->toBe(['grandchild']);
});

test('keeps repeated blocks from empty loops when asked to stay in tree', function () {
    $collector = app(PreviewDataCollector::class);

    // Create parent with repeated block that wasn't rendered (empty loop)
    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['repeated-1', 'dynamic-1'],
    ]);
    $collector->startBlock('parent', $parent);

    // Only dynamic rendered (loop was empty, so repeated-1 never rendered)
    $dynamic1 = BlockData::make([
        'id' => 'dynamic-1',
        'type' => 'button',
        'properties' => [],
        'parentId' => 'parent',
    ]);
    $collector->startBlock('dynamic-1', $dynamic1);

    // Load repeated block into BlockDatastore
    BlockDatastore::shouldReceive('getBlock')
        ->with('repeated-1')
        ->andReturn(BlockData::make([
            'id' => 'repeated-1',
            'type' => 'card',
            'repeated' => true,
            'keepInTree' => true,
            'parentId' => 'parent',
        ]));

    $data = $collector->getCollectedData();

    // Expected: [dynamic-1, repeated-1] (at end since no repeated rendered)
    expect($data['blocks']['parent']['children'])->toBe(['dynamic-1', 'repeated-1']);
    expect($data['blocks'])->toHaveKey('repeated-1');
});

test('drops unrendered static children that are not kept in tree', function () {
    $collector = app(PreviewDataCollector::class);

    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['static-1', 'static-2'],
    ]);
    $collector->startBlock('parent', $parent);

    // static-1 rendered, static-2 sits behind a false condition
    $collector->startBlock('static-1', BlockData::make([
        'id' => 'static-1',
        'type' => 'text',
        'parentId' => 'parent',
        'static' => true,
    ]));

    BlockDatastore::shouldReceive('getBlock')
        ->with('static-2')
        ->andReturn(BlockData::make([
            'id' => 'static-2',
            'type' => 'text',
            'static' => true,
            'parentId' => 'parent',
        ]));

    $data = $collector->getCollectedData();

    expect($data['blocks']['parent']['children'])->toBe(['static-1']);
    expect($data['blocks'])->not->toHaveKey('static-2');
});

test('drops repeated blocks from empty loops that are not kept in tree', function () {
    $collector = app(PreviewDataCollector::class);

    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['repeated-1', 'dynamic-1'],
    ]);
    $collector->startBlock('parent', $parent);

    $collector->startBlock('dynamic-1', BlockData::make([
        'id' => 'dynamic-1',
        'type' => 'button',
        'parentId' => 'parent',
    ]));

    BlockDatastore::shouldReceive('getBlock')
        ->with('repeated-1')
        ->andReturn(BlockData::make([
            'id' => 'repeated-1',
            'type' => 'card',
            'repeated' => true,
            'parentId' => 'parent',
        ]));

    $data = $collector->getCollectedData();

    expect($data['blocks']['parent']['children'])->toBe(['dynamic-1']);
    expect($data['blocks'])->not->toHaveKey('repeated-1');
});

test('recovers the subtree of a kept unrendered static child', function () {
    $collector = app(PreviewDataCollector::class);

    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['static-1'],
    ]);
    $collector->startBlock('parent', $parent);

    BlockDatastore::shouldReceive('getBlock')
        ->with('static-1')
        ->andReturn(BlockData::make([
            'id' => 'static-1',
            'type' => 'container',
            'static' => true,
            'keepInTree' => true,
            'parentId' => 'parent',
            'children' => ['child'],
        ]));

    BlockDatastore::shouldReceive('getBlock')
        ->with('child')
        ->andReturn(BlockData::make([
            'id' => 'child',
            'type' => 'text',
            'parentId' => 'static-1',
        ]));

    $data = $collector->getCollectedData();

    expect($data['blocks'])->toHaveKey('static-1');
    expect($data['blocks'])->toHaveKey('child');
    expect($data['blocks']['static-1']['keepInTree'])->toBeTrue();
});

test('keeps a filtered sibling in a rendered parent children list without collecting it', function () {
    Craftile::checkIfBlockCanRenderUsing(fn (BlockData $block) => $block->id !== 'child-2');
    $collector = app(PreviewDataCollector::class);

    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['child-1', 'child-2', 'child-3'],
    ]);
    $collector->startBlock('parent', $parent);

    foreach (['child-1', 'child-2', 'child-3'] as $childId) {
        $child = BlockData::make([
            'id' => $childId,
            'type' => 'text',
            'properties' => [],
            'parentId' => 'parent',
        ]);
        $collector->startBlock($childId, $child);
        $collector->endBlock($childId);
    }

    $collector->endBlock('parent');

    $data = $collector->getCollectedData();

    expect($data['blocks']['parent']['children'])->toBe(['child-1', 'child-2', 'child-3']);
    expect($data['blocks'])->toHaveKeys(['child-1', 'child-3']);
    expect($data['blocks'])->not->toHaveKey('child-2');
});

test('keeps a filtered root block in the region list without collecting it', function () {
    Craftile::checkIfBlockCanRenderUsing(fn (BlockData $block) => $block->id !== 'block-2');
    $collector = app(PreviewDataCollector::class);

    $collector->startRegion('content');

    foreach (['block-1', 'block-2'] as $blockId) {
        $block = BlockData::make([
            'id' => $blockId,
            'type' => 'text',
            'properties' => [],
        ]);
        $collector->startBlock($blockId, $block);
        $collector->endBlock($blockId);
    }

    $collector->endRegion('content');

    $data = $collector->getCollectedData();

    expect($data['regions'][0]['blocks'])->toBe(['block-1', 'block-2']);
    expect($data['blocks'])->toHaveKey('block-1');
    expect($data['blocks'])->not->toHaveKey('block-2');
});

test('does not recover a filtered static child that is kept in tree', function () {
    Craftile::checkIfBlockCanRenderUsing(fn (BlockData $block) => $block->id !== 'static-1');
    $collector = app(PreviewDataCollector::class);

    $parent = BlockData::make([
        'id' => 'parent',
        'type' => 'container',
        'properties' => [],
        'children' => ['dynamic-1', 'static-1'],
    ]);
    $collector->startBlock('parent', $parent);

    $dynamic1 = BlockData::make([
        'id' => 'dynamic-1',
        'type' => 'button',
        'properties' => [],
        'parentId' => 'parent',
    ]);
    $collector->startBlock('dynamic-1', $dynamic1);
    $collector->endBlock('dynamic-1');

    $static1 = BlockData::make([
        'id' => 'static-1',
        'type' => 'text',
        'properties' => [],
        'parentId' => 'parent',
        'static' => true,
        'keepInTree' => true,
    ]);
    $collector->startBlock('static-1', $static1);
    $collector->endBlock('static-1');
    $collector->endBlock('parent');

    BlockDatastore::shouldReceive('getBlock')->never();

    $data = $collector->getCollectedData();

    expect($data['blocks']['parent']['children'])->toBe(['dynamic-1', 'static-1']);
    expect($data['blocks'])->not->toHaveKey('static-1');
});

test('still collects disabled and ghost blocks the render checker rejects', function () {
    Craftile::checkIfBlockCanRenderUsing(fn (BlockData $block) => false);
    $collector = app(PreviewDataCollector::class);

    $disabled = BlockData::make([
        'id' => 'disabled-1',
        'type' => 'text',
        'properties' => [],
        'disabled' => true,
    ]);
    $collector->startBlock('disabled-1', $disabled);
    $collector->endBlock('disabled-1');

    $ghost = BlockData::make([
        'id' => 'ghost-1',
        'type' => 'text',
        'properties' => [],
        'ghost' => true,
    ]);
    $collector->startBlock('ghost-1', $ghost);
    $collector->endBlock('ghost-1');

    $data = $collector->getCollectedData();

    expect($data['blocks'])->toHaveKeys(['disabled-1', 'ghost-1']);
});
