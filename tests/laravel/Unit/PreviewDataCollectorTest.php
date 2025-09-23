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

    expect($data['regionsBeforeContent'])->toContain('header');
    expect($data['regionsInContent'])->toContain('main');
    expect($data['regionsAfterContent'])->toContain('footer');
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
