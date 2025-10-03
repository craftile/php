<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockPreset;
use Craftile\Core\Data\BlockSchema;
use Craftile\Core\Data\PresetChild;
use Tests\Laravel\Stubs\Discovery\ContainerBlock;

describe('Block Preset Integration', function () {
    it('can create schema from block with array preset', function () {
        $schema = BlockSchema::fromClass(ContainerBlock::class);

        expect($schema->presets)->toHaveLength(1);
        expect($schema->presets[0])->toBeArray();
        expect($schema->presets[0])->toHaveKey('name', 'Hero Section');
        expect($schema->presets[0])->toHaveKey('description');
        expect($schema->presets[0])->toHaveKey('properties');
        expect($schema->presets[0])->toHaveKey('children');
    });

    it('serializes block schema with presets correctly', function () {
        $schema = BlockSchema::fromClass(ContainerBlock::class);
        $array = $schema->toArray();

        expect($array)->toHaveKey('presets');
        expect($array['presets'])->toHaveLength(1);
        expect($array['presets'][0]['name'])->toBe('Hero Section');
        expect($array['presets'][0]['children'])->toHaveLength(3);
        expect($array['presets'][0]['children'][0]['type'])->toBe('text');
        expect($array['presets'][0]['children'][0]['id'])->toBe('heading');
        expect($array['presets'][0]['children'][0]['static'])->toBe(true);
    });

    it('supports fluent API presets', function () {
        // Create a test block class with fluent presets
        $testBlockClass = new class implements \Craftile\Core\Contracts\BlockInterface
        {
            use \Craftile\Core\Concerns\IsBlock;

            protected static string $type = 'test-fluent';

            protected static array $presets = [
                // Note: This would normally be defined as actual instances,
                // but for testing we'll simulate it
            ];

            public function render(): string
            {
                return '<div>Test</div>';
            }
        };

        // Manually set presets using fluent API
        $reflection = new ReflectionClass($testBlockClass);
        $property = $reflection->getProperty('presets');
        $property->setAccessible(true);
        $property->setValue(null, [
            BlockPreset::make('Card Layout')
                ->description('A card with image and text')
                ->properties(['padding' => 16])
                ->blocks([
                    PresetChild::make('image')->id('cover'),
                    PresetChild::make('text')->id('title')->static(),
                ]),
        ]);

        $schema = BlockSchema::fromClass(get_class($testBlockClass));
        $array = $schema->toArray();

        expect($array['presets'])->toHaveLength(1);
        expect($array['presets'][0])->toHaveKey('name', 'Card Layout');
        expect($array['presets'][0]['children'])->toHaveLength(2);
        expect($array['presets'][0]['children'][0]['type'])->toBe('image');
        expect($array['presets'][0]['children'][1]['static'])->toBe(true);
    });

    it('json encodes schema with presets correctly', function () {
        $schema = BlockSchema::fromClass(ContainerBlock::class);
        $json = json_encode($schema);

        expect($json)->toBeString();

        $decoded = json_decode($json, true);
        expect($decoded)->toHaveKey('presets');
        expect($decoded['presets'][0]['name'])->toBe('Hero Section');
    });

    it('handles blocks with no presets', function () {
        // Create a test block without presets
        $testBlockClass = new class implements \Craftile\Core\Contracts\BlockInterface
        {
            use \Craftile\Core\Concerns\IsBlock;

            protected static string $type = 'no-presets';

            public function render(): string
            {
                return '<div>No Presets</div>';
            }
        };

        $schema = BlockSchema::fromClass(get_class($testBlockClass));

        expect($schema->presets)->toBeArray();
        expect($schema->presets)->toBeEmpty();

        $array = $schema->toArray();
        expect($array['presets'])->toBeArray();
        expect($array['presets'])->toBeEmpty();
    });
});
