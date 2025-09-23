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

test('can generate hash', function () {
    $hash1 = $this->cacheManager->generateHash(['type' => 'test-block', 'properties' => ['prop' => 'value']]);
    $hash2 = $this->cacheManager->generateHash(['type' => 'test-block', 'properties' => ['prop' => 'different']]);

    expect($hash1)->toBeString();
    expect($hash2)->toBeString();
    expect($hash1)->not->toBe($hash2);
});

test('same inputs generate same hash', function () {
    $blockData = ['type' => 'test-block', 'properties' => ['prop' => 'value']];
    $hash1 = $this->cacheManager->generateHash($blockData);
    $hash2 = $this->cacheManager->generateHash($blockData);

    expect($hash1)->toBe($hash2);
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

test('handles cache directory creation', function () {
    $newCacheDir = $this->cacheDir.'/nested/path';
    $cacheManager = new BlockCacheManager($this->filesystem);

    expect(fn () => $cacheManager->put('test-hash', 'content'))
        ->not->toThrow(Exception::class);
});
