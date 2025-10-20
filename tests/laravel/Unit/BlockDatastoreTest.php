<?php

use Craftile\Laravel\BlockData;
use Craftile\Laravel\BlockDatastore;
use Craftile\Laravel\View\JsonViewParser;

beforeEach(function () {
    $this->testDir = sys_get_temp_dir().'/craftile-test-'.uniqid();
    mkdir($this->testDir);

    $this->datastore = new BlockDatastore(new JsonViewParser);
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

test('can get block with overrides', function () {
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

    // Get block with overrides - overrides should take precedence
    $overrides = ['properties' => ['content' => 'Overridden', 'color' => 'blue']];
    $block = $this->datastore->getBlock('test-block', $overrides);

    expect($block->property('content'))->toBe('Overridden'); // Override wins
    expect($block->property('color'))->toBe('blue'); // New property added
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
