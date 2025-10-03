<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockData as CoreBlockData;
use Craftile\Laravel\BlockData;
use Craftile\Laravel\EditorAttributes;

describe('BlockData', function () {
    beforeEach(function () {
        $this->blockData = BlockData::make([
            'id' => 'test-block',
            'type' => 'text',
            'properties' => ['content' => 'Test content'],
            'children' => ['child-1', 'child-2'],
        ]);
    });

    it('extends core BlockData', function () {
        expect($this->blockData)->toBeInstanceOf(CoreBlockData::class);
    });

    it('returns EditorAttributes instance from craftileAttributes', function () {
        $attributes = $this->blockData->craftileAttributes();

        expect($attributes)->toBeInstanceOf(EditorAttributes::class);
    });

    it('returns empty array for children when no resolver', function () {
        $children = $this->blockData->children();

        expect($children)->toBe([]);
    });

    it('resolves children using callback', function () {
        $childResolver = function ($id) {
            return BlockData::make([
                'id' => $id,
                'type' => 'text',
                'properties' => ['content' => "Content for $id"],
            ]);
        };

        $blockData = BlockData::make([
            'id' => 'parent',
            'type' => 'container',
            'children' => ['child-1', 'child-2'],
        ], $childResolver);

        $children = $blockData->children();

        expect($children)->toHaveCount(2);
        expect($children[0])->toBeInstanceOf(BlockData::class);
        expect($children[0]->id)->toBe('child-1');
        expect($children[1]->id)->toBe('child-2');
    });

    it('returns childrenIds array', function () {
        $childrenIds = $this->blockData->childrenIds();

        expect($childrenIds)->toBe(['child-1', 'child-2']);
    });

    it('creates PropertyBag with properties', function () {
        $blockData = BlockData::make([
            'id' => 'test',
            'type' => 'text',
            'properties' => ['content' => 'Custom content'],
        ]);

        expect($blockData->properties->get('content'))->toBe('Custom content');
        expect($blockData->properties->has('content'))->toBeTrue();
    });

    it('handles index in make method', function () {
        $blockData = BlockData::make([
            'id' => 'test',
            'type' => 'text',
            'index' => 2,
        ]);

        expect($blockData->index)->toBe(2);
        expect($blockData->iteration)->toBe(3);
    });

    it('preserves index when merged with defaults', function () {
        $blockData = BlockData::make([
            'id' => 'test',
            'type' => 'text',
            'index' => 1,
            'properties' => ['content' => 'Test'],
        ]);

        expect($blockData->index)->toBe(1);
        expect($blockData->iteration)->toBe(2);
    });
});
