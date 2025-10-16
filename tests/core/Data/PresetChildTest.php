<?php

declare(strict_types=1);

use Craftile\Core\Data\PresetChild;

describe('PresetChild', function () {
    it('can be created with type', function () {
        $block = PresetChild::make('text');

        expect($block->toArray())->toHaveKey('type', 'text');
    });

    it('can set id', function () {
        $block = PresetChild::make('text')->id('heading');

        $array = $block->toArray();
        expect($array)->toHaveKey('type', 'text');
        expect($array)->toHaveKey('id', 'heading');
    });

    it('can set properties', function () {
        $block = PresetChild::make('text')
            ->properties(['content' => '<h1>Title</h1>']);

        $array = $block->toArray();
        expect($array)->toHaveKey('properties');
        expect($array['properties'])->toBe(['content' => '<h1>Title</h1>']);
    });

    it('can mark as static', function () {
        $block = PresetChild::make('text')->static();

        $array = $block->toArray();
        expect($array)->toHaveKey('static', true);
    });

    it('does not include static in array when false', function () {
        $block = PresetChild::make('text')->static(false);

        $array = $block->toArray();
        expect($array)->not()->toHaveKey('static');
    });

    it('can mark as repeated', function () {
        $block = PresetChild::make('text')->repeated();

        $array = $block->toArray();
        expect($array)->toHaveKey('repeated', true);
    });

    it('does not include repeated in array when false', function () {
        $block = PresetChild::make('text')->repeated(false);

        $array = $block->toArray();
        expect($array)->not()->toHaveKey('repeated');
    });

    it('can set children using blocks method', function () {
        $block = PresetChild::make('container')
            ->blocks([
                PresetChild::make('text')->id('child1'),
                PresetChild::make('text')->id('child2'),
            ]);

        $array = $block->toArray();
        expect($array)->toHaveKey('children');
        expect($array['children'])->toHaveLength(2);
        expect($array['children'][0])->toHaveKey('type', 'text');
        expect($array['children'][0])->toHaveKey('id', 'child1');
    });

    it('can set children using children method alias', function () {
        $block = PresetChild::make('container')
            ->children([
                PresetChild::make('text')->id('child1'),
            ]);

        $array = $block->toArray();
        expect($array)->toHaveKey('children');
        expect($array['children'])->toHaveLength(1);
    });

    it('supports nested children', function () {
        $block = PresetChild::make('section')
            ->id('hero')
            ->children([
                PresetChild::make('container')
                    ->id('wrapper')
                    ->children([
                        PresetChild::make('text')
                            ->id('title')
                            ->properties(['content' => '<h1>Hero Title</h1>']),
                        PresetChild::make('button')
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
        $block = PresetChild::make('container')
            ->children([
                PresetChild::make('text')->id('obj'),
                ['type' => 'text', 'id' => 'arr'],
            ]);

        $array = $block->toArray();
        expect($array['children'])->toHaveLength(2);
        expect($array['children'][0])->toHaveKey('id', 'obj');
        expect($array['children'][1])->toHaveKey('id', 'arr');
    });

    it('can chain all methods fluently', function () {
        $block = PresetChild::make('text')
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
        $block = PresetChild::make('text')
            ->id('test')
            ->properties(['content' => 'Test']);

        $json = json_encode($block);
        expect($json)->toBeString();

        $decoded = json_decode($json, true);
        expect($decoded)->toHaveKey('type', 'text');
        expect($decoded)->toHaveKey('id', 'test');
    });

    it('omits null and empty values from array', function () {
        $block = PresetChild::make('text');

        $array = $block->toArray();
        expect($array)->toHaveKey('type');
        expect($array)->not()->toHaveKey('id');
        expect($array)->not()->toHaveKey('name');
        expect($array)->not()->toHaveKey('properties');
        expect($array)->not()->toHaveKey('static');
        expect($array)->not()->toHaveKey('children');
    });

    it('can set custom block name', function () {
        $block = PresetChild::make('text')
            ->id('hero-title')
            ->name('Hero Title');

        $array = $block->toArray();
        expect($array)->toHaveKey('type', 'text');
        expect($array)->toHaveKey('id', 'hero-title');
        expect($array)->toHaveKey('name', 'Hero Title');
    });

    it('includes name in toArray output', function () {
        $block = PresetChild::make('text')->name('Custom Name');

        $array = $block->toArray();
        expect($array)->toHaveKey('name', 'Custom Name');
    });

    it('omits name when not set', function () {
        $block = PresetChild::make('text')->id('test');

        $array = $block->toArray();
        expect($array)->toHaveKey('id', 'test');
        expect($array)->not()->toHaveKey('name');
    });

    it('includes name in JSON serialization', function () {
        $block = PresetChild::make('text')
            ->name('Hero Section')
            ->properties(['content' => 'Test']);

        $json = json_encode($block);
        $decoded = json_decode($json, true);

        expect($decoded)->toHaveKey('name', 'Hero Section');
        expect($decoded)->toHaveKey('properties');
    });

    it('supports name with nested children', function () {
        $block = PresetChild::make('section')
            ->id('hero')
            ->name('Hero Section')
            ->children([
                PresetChild::make('text')
                    ->id('title')
                    ->name('Title Text')
                    ->properties(['content' => '<h1>Hero</h1>']),
                PresetChild::make('button')
                    ->id('cta')
                    ->name('Call to Action'),
            ]);

        $array = $block->toArray();
        expect($array)->toHaveKey('name', 'Hero Section');
        expect($array['children'][0])->toHaveKey('name', 'Title Text');
        expect($array['children'][1])->toHaveKey('name', 'Call to Action');
    });

    it('can chain name with other methods fluently', function () {
        $block = PresetChild::make('text')
            ->id('heading')
            ->name('Page Heading')
            ->properties(['content' => '<h1>Title</h1>'])
            ->static();

        $array = $block->toArray();
        expect($array)->toHaveKey('type', 'text');
        expect($array)->toHaveKey('id', 'heading');
        expect($array)->toHaveKey('name', 'Page Heading');
        expect($array)->toHaveKey('properties');
        expect($array)->toHaveKey('static', true);
    });
});
