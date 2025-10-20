<?php

use Craftile\Laravel\Data\UpdateRequest;
use Craftile\Laravel\Support\HandleUpdates;

beforeEach(function () {
    $this->handler = app(HandleUpdates::class);
    $this->tempDir = sys_get_temp_dir().'/craftile_test_'.uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function () {
    // Clean up temp files
    if (file_exists($this->tempDir)) {
        $files = glob($this->tempDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }
});

function createTempTemplate(array $data): string
{
    $tempFile = test()->tempDir.'/template_'.uniqid().'.json';
    file_put_contents($tempFile, json_encode($data, JSON_PRETTY_PRINT));

    return $tempFile;
}

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

    $sourceFile = createTempTemplate($sourceData);

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

    $result = $this->handler->execute($sourceFile, $updateRequest);

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

    $sourceFile = createTempTemplate($sourceData);

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

    $result = $this->handler->execute($sourceFile, $updateRequest, ['main']);

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

    $sourceFile = createTempTemplate($sourceData);

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

    $result = $this->handler->execute($sourceFile, $updateRequest, ['header']);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks']['menu']['properties']['updated'])->toBeTrue();
});

test('handles empty source data', function () {
    $sourceData = [
        'blocks' => [],
        'regions' => [],
    ];

    $sourceFile = createTempTemplate($sourceData);

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

    $result = $this->handler->execute($sourceFile, $updateRequest);

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

    $sourceFile = createTempTemplate($sourceData);

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

    $result = $this->handler->execute($sourceFile, $updateRequest);

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

    $sourceFile = createTempTemplate($sourceData);

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

    $result = $this->handler->execute($sourceFile, $updateRequest);

    expect($result['updated'])->toBeFalse();
    expect($result['data']['blocks'])->toHaveKey('header');
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

    $sourceFile = createTempTemplate($sourceData);

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

    $result = $this->handler->execute($sourceFile, $updateRequest);

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

    $sourceFile = createTempTemplate($sourceData);

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

    $result = $this->handler->execute($sourceFile, $updateRequest, ['header']);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks']['header']['properties']['updated'])->toBeTrue();
    expect($result['data']['regions'])->toHaveCount(1); // Only header region (target region)
    expect(collect($result['data']['regions'])->pluck('name'))->toContain('header');
});

test('handles blocks from update request regions when source is empty', function () {
    $sourceData = [
        'blocks' => [],
        'regions' => [],
    ];

    $sourceFile = createTempTemplate($sourceData);

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

    $result = $this->handler->execute($sourceFile, $updateRequest, ['header']);

    expect($result['updated'])->toBeTrue();
    expect($result['data']['blocks'])->toHaveKey('header');
    expect($result['data']['blocks'])->toHaveKey('nav'); // Child block should be included
});
