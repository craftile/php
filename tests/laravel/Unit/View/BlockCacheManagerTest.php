<?php

use Craftile\Laravel\BlockData;
use Craftile\Laravel\View\BlockCacheManager;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->filesystem = app(Filesystem::class);
    $this->cacheDir = sys_get_temp_dir().'/craftile-test-cache';

    if ($this->filesystem->exists($this->cacheDir)) {
        $this->filesystem->deleteDirectory($this->cacheDir);
    }
    $this->filesystem->makeDirectory($this->cacheDir);

    $this->cacheManager = new BlockCacheManager($this->filesystem);
});

afterEach(function () {
    if ($this->filesystem->exists($this->cacheDir)) {
        $this->filesystem->deleteDirectory($this->cacheDir);
    }
});

test('can generate cache key', function () {
    $key1 = $this->cacheManager->getCacheKey(['id' => 'block1', 'type' => 'test-block', 'properties' => ['prop' => 'value']]);
    $key2 = $this->cacheManager->getCacheKey(['id' => 'block1', 'type' => 'test-block', 'properties' => ['prop' => 'different']]);

    expect($key1)->toBeString();
    expect($key2)->toBeString();
    expect($key1)->not->toBe($key2);
});

test('same inputs generate same cache key', function () {
    $blockData = ['id' => 'block1', 'type' => 'test-block', 'properties' => ['prop' => 'value']];
    $key1 = $this->cacheManager->getCacheKey($blockData);
    $key2 = $this->cacheManager->getCacheKey($blockData);

    expect($key1)->toBe($key2);
});

test('can store and retrieve cached content', function () {
    $hash = 'test-hash';
    $content = '<div>Cached content</div>';

    $this->cacheManager->put($hash, $content);

    expect($this->cacheManager->exists($hash))->toBeTrue();
    expect($this->cacheManager->get($hash))->toBe($content);
});

test('returns null for non-existent cache', function () {
    expect($this->cacheManager->exists('non-existent'))->toBeFalse();
    expect($this->cacheManager->get('non-existent'))->toBeNull();
});

test('can delete cached content', function () {
    $hash = 'test-hash';
    $content = '<div>Cached content</div>';

    $this->cacheManager->put($hash, $content);
    expect($this->cacheManager->exists($hash))->toBeTrue();

    $this->cacheManager->delete($hash);
    expect($this->cacheManager->exists($hash))->toBeFalse();
});

test('can flush all cache', function () {
    $this->cacheManager->put('hash1', 'content1');
    $this->cacheManager->put('hash2', 'content2');

    expect($this->cacheManager->exists('hash1'))->toBeTrue();
    expect($this->cacheManager->exists('hash2'))->toBeTrue();

    $this->cacheManager->flush();

    expect($this->cacheManager->exists('hash1'))->toBeFalse();
    expect($this->cacheManager->exists('hash2'))->toBeFalse();
});

test('can flush specific block cache', function () {
    $blockData1 = ['id' => 'block1', 'type' => 'test', 'properties' => ['content' => 'v1']];
    $blockData2 = ['id' => 'block1', 'type' => 'test', 'properties' => ['content' => 'v2']];
    $blockData3 = ['id' => 'block2', 'type' => 'test', 'properties' => ['content' => 'v1']];

    $key1 = $this->cacheManager->getCacheKey($blockData1);
    $key2 = $this->cacheManager->getCacheKey($blockData2);
    $key3 = $this->cacheManager->getCacheKey($blockData3);

    $this->cacheManager->put($key1, 'content1');
    $this->cacheManager->put($key2, 'content2');
    $this->cacheManager->put($key3, 'content3');

    expect($this->cacheManager->exists($key1))->toBeTrue();
    expect($this->cacheManager->exists($key2))->toBeTrue();
    expect($this->cacheManager->exists($key3))->toBeTrue();

    $this->cacheManager->flushBlock('block1');

    expect($this->cacheManager->exists($key1))->toBeFalse();
    expect($this->cacheManager->exists($key2))->toBeFalse();
    expect($this->cacheManager->exists($key3))->toBeTrue(); // Different block should remain
});

test('handles cache directory creation', function () {
    $newCacheDir = $this->cacheDir.'/nested/path';
    $cacheManager = new BlockCacheManager($this->filesystem);

    expect(fn () => $cacheManager->put('test-hash', 'content'))
        ->not->toThrow(Exception::class);
});

test('flushBlock keeps children files', function () {
    $blockId = 'static-block';
    $childrenKey = $this->cacheManager->childrenKey([['id' => 'child', 'type' => 'test']]);

    $childrenPath = $this->cacheManager->writeChildrenFile($blockId, '<?php echo "children"; ?>', $childrenKey);

    // Compiled views of other templates may still require this file
    $this->cacheManager->flushBlock($blockId);

    expect($this->filesystem->exists($childrenPath))->toBeTrue();

    $this->cacheManager->flushChildrenFile($blockId);
});

test('children key is stable for the same children', function () {
    $children = [['id' => 'a', 'type' => 'text'], ['id' => 'b', 'type' => 'image']];

    expect($this->cacheManager->childrenKey($children))->toBe($this->cacheManager->childrenKey($children));
});

test('children key changes with order, type, static and ghost', function () {
    $base = [['id' => 'a', 'type' => 'text'], ['id' => 'b', 'type' => 'text']];
    $key = $this->cacheManager->childrenKey($base);

    $variants = [
        'order' => [$base[1], $base[0]],
        'type' => [['id' => 'a', 'type' => 'image'], $base[1]],
        'static' => [['id' => 'a', 'type' => 'text', 'static' => true], $base[1]],
        'ghost' => [['id' => 'a', 'type' => 'text', 'ghost' => true], $base[1]],
        'added' => [...$base, ['id' => 'c', 'type' => 'text']],
        'removed' => [$base[0]],
    ];

    foreach ($variants as $variant) {
        expect($this->cacheManager->childrenKey($variant))->not->toBe($key);
    }
});

test('children key ignores properties and other fields', function () {
    $key = $this->cacheManager->childrenKey([['id' => 'a', 'type' => 'text', 'properties' => ['content' => 'v1']]]);

    expect($this->cacheManager->childrenKey([
        ['id' => 'a', 'type' => 'text', 'properties' => ['content' => 'v2'], 'disabled' => true, 'parentId' => 'p'],
    ]))->toBe($key);
});

test('children file path is keyed by children key', function () {
    $keyA = $this->cacheManager->childrenKey([['id' => 'a', 'type' => 'text']]);
    $keyB = $this->cacheManager->childrenKey([['id' => 'b', 'type' => 'text']]);

    expect($this->cacheManager->getChildrenFilePath('group', $keyA))
        ->toEndWith("craftile-children-group-{$keyA}.php")
        ->not->toBe($this->cacheManager->getChildrenFilePath('group', $keyB));
});

test('children file path without a key keeps the legacy name for previously compiled views', function () {
    $hash = hash('xxh128', 'group');

    expect($this->cacheManager->getChildrenFilePath('group'))->toEndWith("craftile-children-group-{$hash}.php");
});

test('children file path for a block matches the key of its resolved children', function () {
    $loaded = [
        'a' => BlockData::make(['id' => 'a', 'type' => 'text', 'properties' => ['content' => 'hello']]),
        'b' => BlockData::make(['id' => 'b', 'type' => 'image', 'static' => true]),
    ];
    $group = BlockData::make(['id' => 'group', 'type' => 'group', 'children' => ['a', 'b']], fn ($id) => $loaded[$id] ?? null);

    $expectedKey = $this->cacheManager->childrenKey([
        ['id' => 'a', 'type' => 'text'],
        ['id' => 'b', 'type' => 'image', 'static' => true],
    ]);

    expect($this->cacheManager->getChildrenFilePathForBlock($group))
        ->toBe($this->cacheManager->getChildrenFilePath('group', $expectedKey));
});

test('children file path for a block is null without children', function () {
    $group = BlockData::make(['id' => 'group', 'type' => 'group'], fn ($id) => null);

    expect($this->cacheManager->getChildrenFilePathForBlock($group))->toBeNull();
});

test('children file path for a block is null when a child is not loaded', function () {
    $loaded = ['a' => BlockData::make(['id' => 'a', 'type' => 'text'])];
    $group = BlockData::make(['id' => 'group', 'type' => 'group', 'children' => ['a', 'missing']], fn ($id) => $loaded[$id] ?? null);

    expect($this->cacheManager->getChildrenFilePathForBlock($group))->toBeNull();
});
