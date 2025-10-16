<?php

use Craftile\Laravel\BlockData;
use Craftile\Laravel\BlockDatastore;
use Craftile\Laravel\PreviewDataCollector;

beforeEach(function () {
    $this->datastore = app(BlockDatastore::class);
    $this->datastore->clear();

    $this->collector = app(PreviewDataCollector::class);
    $this->collector->reset();
});

afterEach(function () {
    $this->datastore->clear();
    $this->collector->reset();
});

describe('Ghost Blocks Integration', function () {
    describe('BlockData', function () {
        it('supports ghost property in Laravel BlockData', function () {
            $blockData = BlockData::make([
                'id' => 'test-1',
                'type' => 'text',
                'ghost' => true,
            ]);

            expect($blockData->ghost)->toBeTrue();
            expect($blockData->isGhost())->toBeTrue();
        });

        it('includes ghost in toArray()', function () {
            $blockData = BlockData::make([
                'id' => 'test-1',
                'type' => 'text',
                'ghost' => true,
            ]);

            $array = $blockData->toArray();

            expect($array['ghost'])->toBeTrue();
        });
    });

    describe('Preview Data Collection', function () {
        it('collects ghost children automatically when parent is collected', function () {
            // Load test data with ghost children
            $testFile = storage_path('test-ghost-blocks.json');
            file_put_contents($testFile, json_encode([
                'blocks' => [
                    'parent-1' => [
                        'id' => 'parent-1',
                        'type' => 'container',
                        'children' => ['child-visible', 'child-ghost', 'child-visible-2'],
                    ],
                    'child-visible' => [
                        'id' => 'child-visible',
                        'type' => 'text',
                        'parentId' => 'parent-1',
                        'properties' => ['content' => 'Visible'],
                    ],
                    'child-ghost' => [
                        'id' => 'child-ghost',
                        'type' => 'meta',
                        'parentId' => 'parent-1',
                        'ghost' => true,
                        'properties' => ['content' => 'Ghost metadata'],
                    ],
                    'child-visible-2' => [
                        'id' => 'child-visible-2',
                        'type' => 'text',
                        'parentId' => 'parent-1',
                        'properties' => ['content' => 'Another visible'],
                    ],
                ],
                'regions' => [
                    ['name' => 'main', 'blocks' => ['parent-1']],
                ],
            ]));

            $this->datastore->loadFile($testFile);

            // Start tracking parent block
            $parentBlock = $this->datastore->getBlock('parent-1');
            $this->collector->startRegion('main');
            $this->collector->startBlock('parent-1', $parentBlock);

            $collected = $this->collector->getCollectedData();

            // Parent should be collected
            expect($collected['blocks'])->toHaveKey('parent-1');

            // Ghost child should be automatically collected
            expect($collected['blocks'])->toHaveKey('child-ghost');
            expect($collected['blocks']['child-ghost']['ghost'])->toBeTrue();

            // Ghost child should be in parent's children array
            expect($collected['blocks']['parent-1']['children'])->toContain('child-ghost');

            // Normal children are not auto-collected (they render normally)
            // They would be collected when they render
            expect($collected['blocks'])->not->toHaveKey('child-visible');

            @unlink($testFile);
        });

        it('only collects ghost children once', function () {
            $testFile = storage_path('test-ghost-duplicate.json');
            file_put_contents($testFile, json_encode([
                'blocks' => [
                    'parent-1' => [
                        'id' => 'parent-1',
                        'type' => 'container',
                        'children' => ['ghost-1'],
                    ],
                    'ghost-1' => [
                        'id' => 'ghost-1',
                        'type' => 'meta',
                        'parentId' => 'parent-1',
                        'ghost' => true,
                        'properties' => ['content' => 'Metadata'],
                    ],
                ],
                'regions' => [
                    ['name' => 'main', 'blocks' => ['parent-1']],
                ],
            ]));

            $this->datastore->loadFile($testFile);

            $parentBlock = $this->datastore->getBlock('parent-1');

            // Call startBlock multiple times (simulating loop or repeated rendering)
            $this->collector->startBlock('parent-1', $parentBlock);
            $this->collector->startBlock('parent-1', $parentBlock);
            $this->collector->startBlock('parent-1', $parentBlock);

            $collected = $this->collector->getCollectedData();

            // Ghost child should only be collected once
            expect($collected['blocks'])->toHaveKey('ghost-1');
            expect($collected['blocks']['ghost-1']['ghost'])->toBeTrue();

            // Ghost child should be in parent's children array (only once)
            expect($collected['blocks']['parent-1']['children'])->toContain('ghost-1');
            expect($collected['blocks']['parent-1']['children'])->toHaveCount(1);

            @unlink($testFile);
        });

        it('handles parent with multiple ghost children', function () {
            $testFile = storage_path('test-multiple-ghosts.json');
            file_put_contents($testFile, json_encode([
                'blocks' => [
                    'page-1' => [
                        'id' => 'page-1',
                        'type' => 'page',
                        'children' => ['meta-title', 'meta-desc', 'hero'],
                    ],
                    'meta-title' => [
                        'id' => 'meta-title',
                        'type' => 'meta-title',
                        'parentId' => 'page-1',
                        'ghost' => true,
                        'properties' => ['content' => 'Page Title'],
                    ],
                    'meta-desc' => [
                        'id' => 'meta-desc',
                        'type' => 'meta-description',
                        'parentId' => 'page-1',
                        'ghost' => true,
                        'properties' => ['content' => 'Page Description'],
                    ],
                    'hero' => [
                        'id' => 'hero',
                        'type' => 'hero',
                        'parentId' => 'page-1',
                        'properties' => ['title' => 'Hero Title'],
                    ],
                ],
                'regions' => [
                    ['name' => 'main', 'blocks' => ['page-1']],
                ],
            ]));

            $this->datastore->loadFile($testFile);

            $pageBlock = $this->datastore->getBlock('page-1');
            $this->collector->startBlock('page-1', $pageBlock);

            $collected = $this->collector->getCollectedData();

            // Both ghost children should be collected
            expect($collected['blocks'])->toHaveKey('meta-title');
            expect($collected['blocks'])->toHaveKey('meta-desc');
            expect($collected['blocks']['meta-title']['ghost'])->toBeTrue();
            expect($collected['blocks']['meta-desc']['ghost'])->toBeTrue();

            // Both ghost children should be in parent's children array
            expect($collected['blocks']['page-1']['children'])->toContain('meta-title');
            expect($collected['blocks']['page-1']['children'])->toContain('meta-desc');

            // Normal child not auto-collected
            expect($collected['blocks'])->not->toHaveKey('hero');

            @unlink($testFile);
        });

        it('does not collect ghost children if they do not exist', function () {
            $testFile = storage_path('test-no-ghosts.json');
            file_put_contents($testFile, json_encode([
                'blocks' => [
                    'parent-1' => [
                        'id' => 'parent-1',
                        'type' => 'container',
                        'children' => ['child-1'],
                    ],
                    'child-1' => [
                        'id' => 'child-1',
                        'type' => 'text',
                        'parentId' => 'parent-1',
                        'properties' => ['content' => 'Normal child'],
                    ],
                ],
                'regions' => [
                    ['name' => 'main', 'blocks' => ['parent-1']],
                ],
            ]));

            $this->datastore->loadFile($testFile);

            $parentBlock = $this->datastore->getBlock('parent-1');
            $this->collector->startBlock('parent-1', $parentBlock);

            $collected = $this->collector->getCollectedData();

            // Only parent should be collected
            expect($collected['blocks'])->toHaveKey('parent-1');
            expect($collected['blocks'])->not->toHaveKey('child-1');

            @unlink($testFile);
        });
    });
});
