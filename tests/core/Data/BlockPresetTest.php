<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockPreset;
use Craftile\Core\Data\PresetChild;

describe('BlockPreset', function () {
    it('can be created with name', function () {
        $preset = BlockPreset::make('Heading and Text');

        expect($preset->toArray())->toHaveKey('name', 'Heading and Text');
    });

    it('can set description', function () {
        $preset = BlockPreset::make('Test Preset')
            ->description('A test preset for testing');

        $array = $preset->toArray();
        expect($array)->toHaveKey('description', 'A test preset for testing');
    });

    it('can set icon', function () {
        $preset = BlockPreset::make('Test Preset')
            ->icon('icon-name');

        $array = $preset->toArray();
        expect($array)->toHaveKey('icon', 'icon-name');
    });

    it('can set category', function () {
        $preset = BlockPreset::make('Test Preset')
            ->category('content');

        $array = $preset->toArray();
        expect($array)->toHaveKey('category', 'content');
    });

    it('can set preview image url', function () {
        $preset = BlockPreset::make('Test Preset')
            ->previewImageUrl('https://example.com/preview.jpg');

        $array = $preset->toArray();
        expect($array)->toHaveKey('previewImageUrl', 'https://example.com/preview.jpg');
    });

    it('can set properties', function () {
        $preset = BlockPreset::make('Test Preset')
            ->properties(['gap' => 12, 'padding' => 20]);

        $array = $preset->toArray();
        expect($array)->toHaveKey('properties');
        expect($array['properties'])->toBe(['gap' => 12, 'padding' => 20]);
    });

    it('can set blocks', function () {
        $preset = BlockPreset::make('Test Preset')
            ->blocks([
                PresetChild::make('text')->id('heading'),
                PresetChild::make('text')->id('description'),
            ]);

        $array = $preset->toArray();
        expect($array)->toHaveKey('children');
        expect($array['children'])->toHaveLength(2);
        expect($array['children'][0])->toHaveKey('type', 'text');
        expect($array['children'][0])->toHaveKey('id', 'heading');
    });

    it('serializes blocks as children in array output', function () {
        $preset = BlockPreset::make('Test')
            ->blocks([
                PresetChild::make('text'),
            ]);

        $array = $preset->toArray();
        expect($array)->toHaveKey('children');
        expect($array)->not()->toHaveKey('blocks');
    });

    it('supports complex nested structure', function () {
        $preset = BlockPreset::make('Hero Section')
            ->description('A hero section with heading and CTA')
            ->properties(['backgroundColor' => '#fff'])
            ->blocks([
                PresetChild::make('container')
                    ->id('hero')
                    ->properties(['padding' => 40])
                    ->children([
                        PresetChild::make('text')
                            ->id('title')
                            ->static()
                            ->properties(['content' => '<h1>Welcome</h1>']),
                        PresetChild::make('button')
                            ->id('cta')
                            ->properties(['label' => 'Get Started']),
                    ]),
            ]);

        $array = $preset->toArray();
        expect($array['name'])->toBe('Hero Section');
        expect($array['description'])->toBe('A hero section with heading and CTA');
        expect($array['properties'])->toBe(['backgroundColor' => '#fff']);
        expect($array['children'])->toHaveLength(1);
        expect($array['children'][0]['id'])->toBe('hero');
        expect($array['children'][0]['children'])->toHaveLength(2);
    });

    it('supports mixed block types (objects and arrays)', function () {
        $preset = BlockPreset::make('Test')
            ->blocks([
                PresetChild::make('text')->id('obj'),
                ['type' => 'text', 'id' => 'arr'],
            ]);

        $array = $preset->toArray();
        expect($array['children'])->toHaveLength(2);
        expect($array['children'][0])->toHaveKey('id', 'obj');
        expect($array['children'][1])->toHaveKey('id', 'arr');
    });

    it('can chain all methods fluently', function () {
        $preset = BlockPreset::make('Full Example')
            ->description('A complete example')
            ->icon('test-icon')
            ->category('layouts')
            ->previewImageUrl('https://example.com/preview.jpg')
            ->properties(['gap' => 16])
            ->blocks([
                PresetChild::make('text')->id('test'),
            ]);

        $array = $preset->toArray();
        expect($array)->toHaveKey('name', 'Full Example');
        expect($array)->toHaveKey('description', 'A complete example');
        expect($array)->toHaveKey('icon', 'test-icon');
        expect($array)->toHaveKey('category', 'layouts');
        expect($array)->toHaveKey('previewImageUrl', 'https://example.com/preview.jpg');
        expect($array)->toHaveKey('properties');
        expect($array)->toHaveKey('children');
    });

    it('is json serializable', function () {
        $preset = BlockPreset::make('Test Preset')
            ->description('Test description')
            ->blocks([
                PresetChild::make('text')->id('test'),
            ]);

        $json = json_encode($preset);
        expect($json)->toBeString();

        $decoded = json_decode($json, true);
        expect($decoded)->toHaveKey('name', 'Test Preset');
        expect($decoded)->toHaveKey('description', 'Test description');
    });

    it('omits null and empty values from array', function () {
        $preset = BlockPreset::make('Minimal');

        $array = $preset->toArray();
        expect($array)->toHaveKey('name');
        expect($array)->not()->toHaveKey('description');
        expect($array)->not()->toHaveKey('icon');
        expect($array)->not()->toHaveKey('category');
        expect($array)->not()->toHaveKey('previewImageUrl');
        expect($array)->not()->toHaveKey('properties');
        expect($array)->not()->toHaveKey('children');
    });

    it('creates a realistic example preset', function () {
        $preset = BlockPreset::make('Heading and Text')
            ->description('Container with a heading and description')
            ->properties(['gap' => 12])
            ->blocks([
                PresetChild::make('text')
                    ->id('heading')
                    ->properties(['content' => '<h2>Title</h2>']),
                PresetChild::make('text')
                    ->id('description')
                    ->properties(['content' => '<p>Description</p>']),
            ]);

        $array = $preset->toArray();

        expect($array)->toBe([
            'name' => 'Heading and Text',
            'description' => 'Container with a heading and description',
            'properties' => ['gap' => 12],
            'children' => [
                [
                    'type' => 'text',
                    'id' => 'heading',
                    'properties' => ['content' => '<h2>Title</h2>'],
                ],
                [
                    'type' => 'text',
                    'id' => 'description',
                    'properties' => ['content' => '<p>Description</p>'],
                ],
            ],
        ]);
    });
});
