<?php

use Craftile\Laravel\Facades\Craftile;

beforeEach(function () {
    Craftile::normalizeTemplateUsing(fn ($data) => $data);
});

afterEach(function () {
    Craftile::normalizeTemplateUsing(fn ($data) => $data);
});

describe('Template Normalizer', function () {
    it('returns template unchanged when no normalizer is registered', function () {
        $template = [
            'blocks' => [
                'block-1' => ['id' => 'block-1', 'type' => 'text'],
            ],
        ];

        $normalized = Craftile::normalizeTemplate($template);

        expect($normalized)->toBe($template);
    });

    it('applies custom normalizer when registered', function () {
        Craftile::normalizeTemplateUsing(function (array $data) {
            if (isset($data['sections'])) {
                $data['blocks'] = $data['sections'];
                unset($data['sections']);
            }

            return $data;
        });

        $template = [
            'sections' => [
                'block-1' => ['id' => 'block-1', 'type' => 'text'],
            ],
        ];

        $normalized = Craftile::normalizeTemplate($template);

        expect($normalized)->toHaveKey('blocks');
        expect($normalized)->not()->toHaveKey('sections');
        expect($normalized['blocks']['block-1'])->toEqual(['id' => 'block-1', 'type' => 'text']);
    });

    it('can normalize nested block properties', function () {
        Craftile::normalizeTemplateUsing(function (array $data) {
            if (isset($data['blocks'])) {
                $data['blocks'] = array_map(function ($block) {
                    if (isset($block['settings'])) {
                        $block['properties'] = $block['settings'];
                        unset($block['settings']);
                    }

                    return $block;
                }, $data['blocks']);
            }

            return $data;
        });

        $template = [
            'blocks' => [
                'block-1' => [
                    'id' => 'block-1',
                    'type' => 'text',
                    'settings' => ['content' => 'Hello'],
                ],
            ],
        ];

        $normalized = Craftile::normalizeTemplate($template);

        expect($normalized['blocks']['block-1'])->toHaveKey('properties');
        expect($normalized['blocks']['block-1'])->not()->toHaveKey('settings');
        expect($normalized['blocks']['block-1']['properties'])->toEqual(['content' => 'Hello']);
    });

    it('can handle complex normalizations', function () {
        Craftile::normalizeTemplateUsing(function (array $data) {
            // Map sections → blocks
            if (isset($data['sections'])) {
                $data['blocks'] = $data['sections'];
                unset($data['sections']);
            }

            // Recursively map settings → properties
            if (isset($data['blocks'])) {
                $data['blocks'] = array_map(function ($block) {
                    return normalizeBlock($block);
                }, $data['blocks']);
            }

            return $data;
        });

        function normalizeBlock(array $block): array
        {
            if (isset($block['settings'])) {
                $block['properties'] = $block['settings'];
                unset($block['settings']);
            }

            if (isset($block['children'])) {
                $block['children'] = array_map(fn ($child) => is_array($child) ? normalizeBlock($child) : $child, $block['children']);
            }

            return $block;
        }

        $template = [
            'sections' => [
                'parent-1' => [
                    'id' => 'parent-1',
                    'type' => 'container',
                    'settings' => ['bg' => 'red'],
                    'children' => [
                        [
                            'id' => 'child-1',
                            'type' => 'text',
                            'settings' => ['content' => 'Hello'],
                        ],
                    ],
                ],
            ],
        ];

        $normalized = Craftile::normalizeTemplate($template);

        expect($normalized)->toHaveKey('blocks');
        expect($normalized)->not()->toHaveKey('sections');
        expect($normalized['blocks']['parent-1'])->toHaveKey('properties');
        expect($normalized['blocks']['parent-1'])->not()->toHaveKey('settings');
        expect($normalized['blocks']['parent-1']['children'][0])->toHaveKey('properties');
        expect($normalized['blocks']['parent-1']['children'][0])->not()->toHaveKey('settings');
    });

    it('normalizer is called in BlockDatastore', function () {
        $called = false;

        Craftile::normalizeTemplateUsing(function (array $data) use (&$called) {
            $called = true;

            return $data;
        });

        $testFile = storage_path('test-normalizer.json');
        file_put_contents($testFile, json_encode([
            'blocks' => [
                'test-1' => ['id' => 'test-1', 'type' => 'text'],
            ],
        ]));

        $datastore = app(\Craftile\Laravel\BlockDatastore::class);
        $datastore->loadFile($testFile);

        expect($called)->toBeTrue();

        @unlink($testFile);
    });

    it('normalizer is called in HandleUpdates', function () {
        $called = false;

        Craftile::normalizeTemplateUsing(function (array $data) use (&$called) {
            $called = true;

            return $data;
        });

        $handler = app(\Craftile\Laravel\Support\HandleUpdates::class);

        $testFile = sys_get_temp_dir().'/craftile_test_'.uniqid().'.json';
        file_put_contents($testFile, json_encode([
            'blocks' => [
                'block-1' => ['id' => 'block-1', 'type' => 'text'],
            ],
        ]));

        $updateRequest = \Craftile\Laravel\Data\UpdateRequest::make([
            'blocks' => [],
            'regions' => [],
            'changes' => ['added' => [], 'changed' => [], 'removed' => []],
        ]);

        $handler->execute($testFile, $updateRequest);

        expect($called)->toBeTrue();

        @unlink($testFile);
    });
});
