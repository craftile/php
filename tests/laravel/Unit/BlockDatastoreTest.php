<?php

use Craftile\Laravel\BlockData;
use Craftile\Laravel\BlockDatastore;
use Craftile\Laravel\BlockFlattener;
use Craftile\Laravel\Facades\Craftile;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->testDir = sys_get_temp_dir().'/craftile-test-'.uniqid();
    mkdir($this->testDir);

    $this->datastore = new BlockDatastore(app(BlockFlattener::class));
});

afterEach(function () {
    if (is_dir($this->testDir)) {
        array_map('unlink', glob("{$this->testDir}/*"));
        rmdir($this->testDir);
    }

    $this->datastore->clear();
});

test('can load blocks from JSON file', function () {
    $blocksData = [
        'blocks' => [
            'block-1' => [
                'id' => 'block-1',
                'type' => 'text',
                'properties' => ['content' => 'Hello World'],
                'children' => [],
            ],
        ],
    ];

    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    $this->datastore->loadFile($filePath);

    expect($this->datastore->hasBlock('block-1'))->toBeTrue();

    $block = $this->datastore->getBlock('block-1');
    expect($block)->toBeInstanceOf(BlockData::class);
    expect($block->id)->toBe('block-1');
    expect($block->type)->toBe('text');
    expect($block->property('content'))->toBe('Hello World');
});

test('can load blocks from YAML file', function () {
    $yamlContent = <<<'YAML'
blocks:
  block-1:
    id: block-1
    type: text
    properties:
      content: "Hello from YAML"
    children: []
YAML;

    $filePath = $this->testDir.'/blocks.yml';
    file_put_contents($filePath, $yamlContent);

    $this->datastore->loadFile($filePath);

    expect($this->datastore->hasBlock('block-1'))->toBeTrue();

    $block = $this->datastore->getBlock('block-1');
    expect($block->property('content'))->toBe('Hello from YAML');
});

test('returns null for non-existent blocks', function () {
    $result = $this->datastore->getBlock('non-existent');
    expect($result)->toBeNull();
});

test('can check if block exists', function () {
    expect($this->datastore->hasBlock('test-block'))->toBeFalse();

    $blocksData = [
        'blocks' => [
            'test-block' => [
                'id' => 'test-block',
                'type' => 'text',
                'properties' => [],
                'children' => [],
            ],
        ],
    ];

    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    $this->datastore->loadFile($filePath);

    expect($this->datastore->hasBlock('test-block'))->toBeTrue();
});

test('can get block with defaults', function () {
    $blocksData = [
        'blocks' => [
            'test-block' => [
                'id' => 'test-block',
                'type' => 'text',
                'properties' => ['content' => 'Original'],
                'children' => [],
            ],
        ],
    ];

    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    $this->datastore->loadFile($filePath);

    // Get block with defaults - existing data should take precedence
    $defaults = ['properties' => ['content' => 'Default', 'color' => 'blue']];
    $block = $this->datastore->getBlock('test-block', $defaults);

    expect($block->property('content'))->toBe('Original'); // Original value preserved
    expect($block->property('color'))->toBe('blue'); // Default added
});

test('handles non-existent files gracefully', function () {
    $this->datastore->loadFile('/non/existent/file.json');

    expect($this->datastore->hasBlock('any-block'))->toBeFalse();
});

test('handles invalid JSON gracefully', function () {
    $filePath = $this->testDir.'/invalid.json';
    file_put_contents($filePath, '{ invalid json }');

    $this->datastore->loadFile($filePath);

    expect($this->datastore->hasBlock('any-block'))->toBeFalse();
});

test('can clear all loaded blocks', function () {
    $blocksData = [
        'blocks' => [
            'block-1' => ['id' => 'block-1', 'type' => 'text', 'properties' => [], 'children' => []],
            'block-2' => ['id' => 'block-2', 'type' => 'text', 'properties' => [], 'children' => []],
        ],
    ];

    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    $this->datastore->loadFile($filePath);

    expect($this->datastore->hasBlock('block-1'))->toBeTrue();
    expect($this->datastore->hasBlock('block-2'))->toBeTrue();

    $this->datastore->clear();

    expect($this->datastore->hasBlock('block-1'))->toBeFalse();
    expect($this->datastore->hasBlock('block-2'))->toBeFalse();
});

test('can get blocks array from file', function () {
    $blocksData = [
        'blocks' => [
            'block-1' => ['id' => 'block-1', 'type' => 'text', 'properties' => [], 'children' => []],
            'block-2' => ['id' => 'block-2', 'type' => 'image', 'properties' => [], 'children' => []],
        ],
    ];

    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    $result = $this->datastore->getBlocksArray($filePath);

    expect($result)->toHaveCount(2);
    expect($result)->toHaveKey('block-1');
    expect($result)->toHaveKey('block-2');
    expect($result['block-1']['type'])->toBe('text');
    expect($result['block-2']['type'])->toBe('image');
});

test('uses in-memory cache only in preview mode', function () {
    $blocksData = [
        'blocks' => [
            'test-block' => ['id' => 'test-block', 'type' => 'text', 'properties' => [], 'children' => []],
        ],
    ];
    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    // Clear all caches
    $this->datastore->clear();
    Cache::flush();

    Craftile::shouldReceive('inPreview')->andReturn(true);

    // First call should parse file (no Laravel cache in preview mode)
    $result1 = $this->datastore->getBlocksArray($filePath);
    expect($result1)->toHaveCount(1);

    // Second call should use in-memory cache
    $result2 = $this->datastore->getBlocksArray($filePath);
    expect($result2)->toEqual($result1);

    // Verify Laravel cache was not used by checking it's empty
    $cacheKey = 'craftile_blocks_'.md5($filePath.'_'.filemtime($filePath));
    expect(Cache::get($cacheKey))->toBeNull();
});

test('uses Laravel cache in production mode', function () {
    $blocksData = [
        'blocks' => [
            'test-block' => ['id' => 'test-block', 'type' => 'text', 'properties' => [], 'children' => []],
        ],
    ];
    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    $this->datastore->clear();
    Cache::flush();

    Craftile::shouldReceive('inPreview')->andReturn(false);

    // First call should store in Laravel cache
    $result1 = $this->datastore->getBlocksArray($filePath);
    expect($result1)->toHaveCount(1);

    // Verify Laravel cache was used
    $cacheKey = 'craftile_blocks_'.md5($filePath.'_'.filemtime($filePath));
    expect(Cache::get($cacheKey))->toEqual($result1);

    $this->datastore->clear();

    // Second call should retrieve from Laravel cache
    $result2 = $this->datastore->getBlocksArray($filePath);
    expect($result2)->toEqual($result1);
});

test('uses configurable cache TTL', function () {
    $blocksData = [
        'blocks' => [
            'test-block' => ['id' => 'test-block', 'type' => 'text', 'properties' => [], 'children' => []],
        ],
    ];
    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    config(['craftile.cache.ttl' => 7200]);

    $this->datastore->clear();
    Cache::flush();

    Craftile::shouldReceive('inPreview')->andReturn(false);

    Cache::shouldReceive('remember')
        ->once()
        ->withArgs(function ($key, $ttl, $callback) {
            return is_string($key) && $ttl === 7200 && is_callable($callback);
        })
        ->andReturn($blocksData['blocks']);

    $result = $this->datastore->getBlocksArray($filePath);
    expect($result)->toHaveCount(1);
});

test('defaults to 1 hour cache TTL when not configured', function () {
    $blocksData = [
        'blocks' => [
            'test-block' => ['id' => 'test-block', 'type' => 'text', 'properties' => [], 'children' => []],
        ],
    ];
    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    config(['craftile.cache' => []]);

    $this->datastore->clear();
    Cache::flush();

    Craftile::shouldReceive('inPreview')->andReturn(false);

    Cache::shouldReceive('remember')
        ->once()
        ->withArgs(function ($key, $ttl, $callback) {
            return is_string($key) && $ttl === 3600 && is_callable($callback);
        })
        ->andReturn($blocksData['blocks']);

    $result = $this->datastore->getBlocksArray($filePath);
    expect($result)->toHaveCount(1);
});

test('binds source file to BlockData instances', function () {
    $blocksData = [
        'blocks' => [
            'test-block' => ['id' => 'test-block', 'type' => 'text', 'properties' => [], 'children' => []],
        ],
    ];
    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));

    $this->datastore->loadFile($filePath);
    $block = $this->datastore->getBlock('test-block');

    expect($block)->not->toBeNull();
    expect($block->getSourceFile())->toBe($filePath);
});

test('preserves source file when getting block with defaults', function () {
    $blocksData = ['blocks' => ['test-block' => ['id' => 'test-block', 'type' => 'text', 'properties' => ['content' => 'Hello'], 'children' => []]]];
    $filePath = $this->testDir.'/blocks.json';
    file_put_contents($filePath, json_encode($blocksData));
    $this->datastore->loadFile($filePath);
    $block = $this->datastore->getBlock('test-block', ['properties' => ['title' => 'Default']]);
    expect($block)->not->toBeNull();
    expect($block->getSourceFile())->toBe($filePath);
    expect($block->property('content'))->toBe('Hello');
    expect($block->property('title'))->toBe('Default');
});
