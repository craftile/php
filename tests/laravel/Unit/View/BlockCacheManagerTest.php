<?php

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
