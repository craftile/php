<?php

use Craftile\Laravel\BlockFlattener;
use Craftile\Laravel\Data\UpdateRequest;
use Craftile\Laravel\Support\HandleUpdates;

beforeEach(function () {
    $this->flattener = app(BlockFlattener::class);
    $this->handler = new HandleUpdates($this->flattener);
});

test('can execute updates without region filtering', function () {
    $sourceData = [
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => ['nav']],
            'nav' => ['id' => 'nav', 'type' => 'nav', 'parentId' => 'header', 'children' => []],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
        ],
    ];

    $updateRequest = UpdateRequest::make([
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => ['nav'], 'properties' => ['title' => 'Updated']],
            'footer' => ['id' => 'footer', 'type' => 'footer', 'children' => []],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
            ['name' => 'footer', 'blocks' => ['footer']],
        ],
        'changes' => [
            'added' => ['footer'],
            'updated' => ['header'],
            'removed' => [],
            'moved' => [],
        ],
    ]);

    $result = $this->handler->execute($sourceData, $updateRequest);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks'])->toHaveKey('header');
    expect($result['data']['blocks'])->toHaveKey('footer');
    expect($result['data']['blocks']['header']['properties']['title'])->toBe('Updated');
    expect($result['data']['regions'])->toHaveCount(2);
});

test('can execute updates with region filtering', function () {
    $sourceData = [
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => ['nav']],
            'nav' => ['id' => 'nav', 'type' => 'nav', 'parentId' => 'header', 'children' => []],
            'footer' => ['id' => 'footer', 'type' => 'footer', 'children' => []],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
            ['name' => 'footer', 'blocks' => ['footer']],
        ],
    ];

    $updateRequest = UpdateRequest::make([
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => ['nav'], 'properties' => ['title' => 'Updated']],
            'footer' => ['id' => 'footer', 'type' => 'footer', 'children' => [], 'properties' => ['text' => 'Updated Footer']],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
            ['name' => 'footer', 'blocks' => ['footer']],
        ],
        'changes' => [
            'added' => [],
            'updated' => ['header', 'footer'],
            'removed' => [],
            'moved' => [],
        ],
    ]);

    $result = $this->handler->execute($sourceData, $updateRequest, ['main']);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks']['header']['properties']['title'])->toBe('Updated');
    expect($result['data']['blocks']['footer'])->not->toHaveKey('properties'); // Footer not updated
});

test('detects nested blocks in target regions', function () {
    $sourceData = [
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => ['nav']],
            'nav' => ['id' => 'nav', 'type' => 'nav', 'parentId' => 'header', 'children' => ['menu']],
            'menu' => ['id' => 'menu', 'type' => 'menu', 'parentId' => 'nav', 'children' => []],
        ],
        'regions' => [
            ['name' => 'header', 'blocks' => ['header']],
        ],
    ];

    $updateRequest = UpdateRequest::make([
        'blocks' => [
            'menu' => ['id' => 'menu', 'type' => 'menu', 'parentId' => 'nav', 'children' => [], 'properties' => ['updated' => true]],
        ],
        'regions' => [
            ['name' => 'header', 'blocks' => ['header']],
        ],
        'changes' => [
            'added' => [],
            'updated' => ['menu'],
            'removed' => [],
            'moved' => [],
        ],
    ]);

    $result = $this->handler->execute($sourceData, $updateRequest, ['header']);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks']['menu']['properties']['updated'])->toBeTrue();
});

test('handles empty source data', function () {
    $sourceData = [];

    $updateRequest = UpdateRequest::make([
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => []],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
        ],
        'changes' => [
            'added' => ['header'],
            'updated' => [],
            'removed' => [],
            'moved' => [],
        ],
    ]);

    $result = $this->handler->execute($sourceData, $updateRequest);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks'])->toHaveKey('header');
    expect($result['data']['regions'])->toHaveCount(1);
});

test('handles nested source data by flattening', function () {
    $sourceData = [
        'blocks' => [
            'header' => [
                'id' => 'header',
                'type' => 'header',
                'children' => [
                    'nav' => [
                        'id' => 'nav',
                        'type' => 'nav',
                        'children' => [],
                    ],
                ],
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
        ],
    ];

    $updateRequest = UpdateRequest::make([
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => ['nav'], 'properties' => ['updated' => true]],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
        ],
        'changes' => [
            'added' => [],
            'updated' => ['header'],
            'removed' => [],
            'moved' => [],
        ],
    ]);

    $result = $this->handler->execute($sourceData, $updateRequest);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks']['header']['properties']['updated'])->toBeTrue();
});

test('returns false when no changes are made', function () {
    $sourceData = [
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => []],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
        ],
    ];

    $updateRequest = UpdateRequest::make([
        'blocks' => [], // No blocks in update request
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']], // Same regions
        ],
        'changes' => [
            'added' => [],
            'updated' => [],
            'removed' => [],
            'moved' => [],
        ],
    ]);

    $result = $this->handler->execute($sourceData, $updateRequest);

    expect($result['updated'])->toBeFalse();
    expect($result['data'])->toBe($sourceData);
});

test('removes blocks correctly', function () {
    $sourceData = [
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => []],
            'footer' => ['id' => 'footer', 'type' => 'footer', 'children' => []],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header', 'footer']],
        ],
    ];

    $updateRequest = UpdateRequest::make([
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => []],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
        ],
        'changes' => [
            'added' => [],
            'updated' => [],
            'removed' => ['footer'],
            'moved' => [],
        ],
    ]);

    $result = $this->handler->execute($sourceData, $updateRequest);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks'])->not->toHaveKey('footer');
    expect($result['data']['blocks'])->toHaveKey('header');
});

test('only updates target regions when filtering', function () {
    $sourceData = [
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => []],
            'footer' => ['id' => 'footer', 'type' => 'footer', 'children' => []],
        ],
        'regions' => [
            ['name' => 'header', 'blocks' => ['header']],
            ['name' => 'footer', 'blocks' => ['footer']],
        ],
    ];

    $updateRequest = UpdateRequest::make([
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => [], 'properties' => ['updated' => true]],
        ],
        'regions' => [
            ['name' => 'header', 'blocks' => ['header']],
            ['name' => 'footer', 'blocks' => ['footer']],
            ['name' => 'sidebar', 'blocks' => ['sidebar']], // New region
        ],
        'changes' => [
            'added' => [],
            'updated' => ['header'],
            'removed' => [],
            'moved' => [],
        ],
    ]);

    $result = $this->handler->execute($sourceData, $updateRequest, ['header']);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks']['header']['properties']['updated'])->toBeTrue();
    expect($result['data']['regions'])->toHaveCount(1); // Only header region (target region)
    expect(collect($result['data']['regions'])->pluck('name'))->toContain('header');
});

test('handles blocks from update request regions when source is empty', function () {
    $sourceData = [];

    $updateRequest = UpdateRequest::make([
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'header', 'children' => ['nav']],
            'nav' => ['id' => 'nav', 'type' => 'nav', 'parentId' => 'header', 'children' => []],
        ],
        'regions' => [
            ['name' => 'header', 'blocks' => ['header']],
        ],
        'changes' => [
            'added' => ['header', 'nav'],
            'updated' => [],
            'removed' => [],
            'moved' => [],
        ],
    ]);

    $result = $this->handler->execute($sourceData, $updateRequest, ['header']);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks'])->toHaveKey('header');
    expect($result['data']['blocks'])->toHaveKey('nav'); // Child block should be included
});
