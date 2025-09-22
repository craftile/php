<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockData;
use Craftile\Core\Data\PropertyBag;

describe('BlockData', function () {
    it('can be created with all parameters', function () {
        $properties = new PropertyBag(['content' => 'Hello World', 'size' => 'large']);

        $block = new BlockData(
            id: 'test-block-1',
            type: 'text',
            properties: $properties,
            parentId: 'parent-1',
            childrenIds: ['child-1', 'child-2'],
            disabled: true,
            static: true,
            repeated: true,
            semanticId: 'hero-text',
            resolveChildData: fn($id) => "child-$id"
        );

        expect($block->id)->toBe('test-block-1');
        expect($block->type)->toBe('text');
        expect($block->properties)->toBe($properties);
        expect($block->parentId)->toBe('parent-1');
        expect($block->childrenIds)->toBe(['child-1', 'child-2']);
        expect($block->disabled)->toBeTrue();
        expect($block->static)->toBeTrue();
        expect($block->repeated)->toBeTrue();
        expect($block->semanticId)->toBe('hero-text');
    });

    it('has sensible defaults for optional parameters', function () {
        $properties = new PropertyBag([]);

        $block = new BlockData(
            id: 'test-block',
            type: 'text',
            properties: $properties
        );

        expect($block->parentId)->toBeNull();
        expect($block->childrenIds)->toBe([]);
        expect($block->disabled)->toBeFalse();
        expect($block->static)->toBeFalse();
        expect($block->repeated)->toBeFalse();
        expect($block->semanticId)->toBeNull();
    });

    it('can be created from array data using make method', function () {
        $data = [
            'id' => 'text-1',
            'type' => 'text',
            'properties' => [
                'content' => 'Hello World',
                'fontSize' => 16
            ],
            'parentId' => 'section-1',
            'children' => ['image-1', 'button-1'],
            'disabled' => false,
            'static' => true,
            'repeated' => false,
            'semanticId' => 'main-heading'
        ];

        $block = BlockData::make($data);

        expect($block->id)->toBe('text-1');
        expect($block->type)->toBe('text');
        expect($block->properties)->toBeInstanceOf(PropertyBag::class);
        expect($block->properties->get('content'))->toBe('Hello World');
        expect($block->properties->get('fontSize'))->toBe(16);
        expect($block->parentId)->toBe('section-1');
        expect($block->childrenIds)->toBe(['image-1', 'button-1']);
        expect($block->disabled)->toBeFalse();
        expect($block->static)->toBeTrue();
        expect($block->repeated)->toBeFalse();
        expect($block->semanticId)->toBe('main-heading');
    });

    it('handles missing array fields with defaults when using make', function () {
        $data = [
            'id' => 'simple-block',
            'type' => 'text'
        ];

        $block = BlockData::make($data);

        expect($block->id)->toBe('simple-block');
        expect($block->type)->toBe('text');
        expect($block->properties)->toBeInstanceOf(PropertyBag::class);
        expect($block->parentId)->toBeNull();
        expect($block->childrenIds)->toBe([]);
        expect($block->disabled)->toBeFalse();
        expect($block->static)->toBeFalse();
        expect($block->repeated)->toBeFalse();
        expect($block->semanticId)->toBeNull();
    });

    it('can resolve child data using callback', function () {
        $resolveChildData = function () {
            return "Resolved child data";
        };

        $block = BlockData::make([
            'id' => 'parent',
            'type' => 'container',
            'children' => ['child-1', 'child-2']
        ], $resolveChildData);

        $childData = $block->getChildData();

        expect($childData)->toBe('Resolved child data');
    });

    it('returns null when no resolver provided', function () {
        $block = BlockData::make([
            'id' => 'single',
            'type' => 'text'
        ]);

        expect($block->getChildData())->toBeNull();
    });

    it('has children and can count them', function () {
        $block = BlockData::make([
            'id' => 'parent',
            'type' => 'container',
            'children' => ['child-1', 'child-2']
        ]);

        expect($block->hasChildren())->toBeTrue();
        expect($block->childrenCount())->toBe(2);
        expect($block->childrenIds)->toBe(['child-1', 'child-2']);
    });

    it('can be converted to array', function () {
        $data = [
            'id' => 'test-block',
            'type' => 'text',
            'properties' => ['content' => 'Test'],
            'parentId' => 'parent',
            'children' => ['child-1'],
            'disabled' => true,
            'static' => false,
            'repeated' => true,
            'semanticId' => 'test-semantic'
        ];

        $block = BlockData::make($data);
        $array = $block->toArray();

        expect($array)->toHaveKey('id', 'test-block');
        expect($array)->toHaveKey('type', 'text');
        expect($array)->toHaveKey('properties');
        expect($array['properties'])->toHaveKey('content', 'Test');
        expect($array)->toHaveKey('parentId', 'parent');
        expect($array)->toHaveKey('children', ['child-1']);
        expect($array)->toHaveKey('disabled', true);
        expect($array)->toHaveKey('static', false);
        expect($array)->toHaveKey('repeated', true);
        expect($array)->toHaveKey('semanticId', 'test-semantic');
    });

    it('is json serializable', function () {
        $block = BlockData::make([
            'id' => 'json-test',
            'type' => 'text',
            'properties' => ['content' => 'JSON Test']
        ]);

        $json = json_encode($block);
        expect($json)->toBeString();

        $decoded = json_decode($json, true);
        expect($decoded)->toHaveKey('id', 'json-test');
        expect($decoded)->toHaveKey('type', 'text');
        expect($decoded['properties'])->toHaveKey('content', 'JSON Test');
    });
});