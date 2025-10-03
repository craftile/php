<?php

declare(strict_types=1);

use Craftile\Core\Data\PresetBlock;

describe('PresetBlock', function () {
    it('can be created with type', function () {
        $block = PresetBlock::make('text');

        expect($block->toArray())->toHaveKey('type', 'text');
    });

    it('can set id', function () {
        $block = PresetBlock::make('text')->id('heading');

        $array = $block->toArray();
        expect($array)->toHaveKey('type', 'text');
        expect($array)->toHaveKey('id', 'heading');
    });

    it('can set properties', function () {
        $block = PresetBlock::make('text')
            ->properties(['content' => '<h1>Title</h1>']);

        $array = $block->toArray();
        expect($array)->toHaveKey('properties');
        expect($array['properties'])->toBe(['content' => '<h1>Title</h1>']);
    });

    it('can mark as static', function () {
        $block = PresetBlock::make('text')->static();

        $array = $block->toArray();
        expect($array)->toHaveKey('static', true);
    });

    it('does not include static in array when false', function () {
        $block = PresetBlock::make('text')->static(false);

        $array = $block->toArray();
        expect($array)->not()->toHaveKey('static');
    });

    it('can set children using blocks method', function () {
        $block = PresetBlock::make('container')
            ->blocks([
                PresetBlock::make('text')->id('child1'),
                PresetBlock::make('text')->id('child2'),
            ]);

        $array = $block->toArray();
        expect($array)->toHaveKey('children');
        expect($array['children'])->toHaveLength(2);
        expect($array['children'][0])->toHaveKey('type', 'text');
        expect($array['children'][0])->toHaveKey('id', 'child1');
    });

    it('can set children using children method alias', function () {
        $block = PresetBlock::make('container')
            ->children([
                PresetBlock::make('text')->id('child1'),
            ]);

        $array = $block->toArray();
        expect($array)->toHaveKey('children');
        expect($array['children'])->toHaveLength(1);
    });

    it('supports nested children', function () {
        $block = PresetBlock::make('section')
            ->id('hero')
            ->children([
                PresetBlock::make('container')
                    ->id('wrapper')
                    ->children([
                        PresetBlock::make('text')
                            ->id('title')
                            ->properties(['content' => '<h1>Hero Title</h1>']),
                        PresetBlock::make('button')
                            ->id('cta')
                            ->properties(['label' => 'Get Started']),
                    ]),
            ]);

        $array = $block->toArray();
        expect($array['children'][0]['children'])->toHaveLength(2);
        expect($array['children'][0]['children'][0]['id'])->toBe('title');
        expect($array['children'][0]['children'][1]['id'])->toBe('cta');
    });

    it('supports mixed child types (objects and arrays)', function () {
        $block = PresetBlock::make('container')
            ->children([
                PresetBlock::make('text')->id('obj'),
                ['type' => 'text', 'id' => 'arr'],
            ]);

        $array = $block->toArray();
        expect($array['children'])->toHaveLength(2);
        expect($array['children'][0])->toHaveKey('id', 'obj');
        expect($array['children'][1])->toHaveKey('id', 'arr');
    });

    it('can chain all methods fluently', function () {
        $block = PresetBlock::make('text')
            ->id('heading')
            ->properties(['content' => '<h1>Title</h1>'])
            ->static();

        $array = $block->toArray();
        expect($array)->toHaveKey('type', 'text');
        expect($array)->toHaveKey('id', 'heading');
        expect($array)->toHaveKey('properties');
        expect($array)->toHaveKey('static', true);
    });

    it('is json serializable', function () {
        $block = PresetBlock::make('text')
            ->id('test')
            ->properties(['content' => 'Test']);

        $json = json_encode($block);
        expect($json)->toBeString();

        $decoded = json_decode($json, true);
        expect($decoded)->toHaveKey('type', 'text');
        expect($decoded)->toHaveKey('id', 'test');
    });

    it('omits null and empty values from array', function () {
        $block = PresetBlock::make('text');

        $array = $block->toArray();
        expect($array)->toHaveKey('type');
        expect($array)->not()->toHaveKey('id');
        expect($array)->not()->toHaveKey('properties');
        expect($array)->not()->toHaveKey('static');
        expect($array)->not()->toHaveKey('children');
    });
});
