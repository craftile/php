<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockPreset;
use Craftile\Core\Data\BlockSchema;
use Craftile\Core\Data\PresetChild;
use Craftile\Core\Services\BlockSchemaRegistry;

// Test preset class for testing class string registration
class TestPresetClass extends BlockPreset
{
    protected function build(): void
    {
        $this->name('Test Preset From Class')
            ->description('A test preset created from a class');
    }
}

// Another test preset class
class AnotherTestPreset extends BlockPreset
{
    protected function build(): void
    {
        $this->name('Another Test Preset')
            ->description('Another test preset');
    }
}

describe('BlockSchemaRegistry - Custom Preset Registration', function () {
    beforeEach(function () {
        $this->registry = new BlockSchemaRegistry;
    });

    it('can register custom preset to existing block type', function () {
        $schema = new BlockSchema(
            type: 'container',
            slug: 'container',
            class: TestBlock::class,
            name: 'Container',
            presets: [
                BlockPreset::make('Default Layout'),
            ]
        );
        $this->registry->register($schema);

        $customPreset = BlockPreset::make('Custom Layout');
        $this->registry->registerPreset('container', $customPreset);

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(2);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('Default Layout');
        expect($retrieved->presets[1]->toArray()['name'])->toBe('Custom Layout');
    });

    it('can register preset using class string', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $this->registry->registerPreset('container', TestPresetClass::class);

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(1);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('Test Preset From Class');
    });

    it('can register preset using instance', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $preset = BlockPreset::make('Inline Preset')
            ->description('Created inline')
            ->properties(['gap' => 16]);

        $this->registry->registerPreset('container', $preset);

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(1);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('Inline Preset');
        expect($retrieved->presets[0]->toArray()['description'])->toBe('Created inline');
    });

    it('can register preset using array', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $this->registry->registerPreset('container', [
            'name' => 'Array Preset',
            'description' => 'Created from array',
            'properties' => ['padding' => 20],
        ]);

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(1);
        expect($retrieved->presets[0])->toBe([
            'name' => 'Array Preset',
            'description' => 'Created from array',
            'properties' => ['padding' => 20],
        ]);
    });

    it('can register preset before schema is registered', function () {
        $this->registry->registerPreset('container', BlockPreset::make('Custom'));

        $schema = new BlockSchema(
            type: 'container',
            slug: 'container',
            class: TestBlock::class,
            name: 'Container',
            presets: [
                BlockPreset::make('Default'),
            ]
        );
        $this->registry->register($schema);

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(2);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('Default');
        expect($retrieved->presets[1]->toArray()['name'])->toBe('Custom');
    });

    it('can register multiple presets before schema registration', function () {
        $this->registry->registerPreset('text', BlockPreset::make('First'));
        $this->registry->registerPreset('text', BlockPreset::make('Second'));
        $this->registry->registerPreset('text', TestPresetClass::class);

        $schema = new BlockSchema('text', 'text', TestBlock::class, 'Text');
        $this->registry->register($schema);

        $retrieved = $this->registry->get('text');
        expect($retrieved->presets)->toHaveCount(3);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('First');
        expect($retrieved->presets[1]->toArray()['name'])->toBe('Second');
        expect($retrieved->presets[2]->toArray()['name'])->toBe('Test Preset From Class');
    });

    it('supports multiple preset formats', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $this->registry->registerPreset('container', TestPresetClass::class);
        $this->registry->registerPreset('container', BlockPreset::make('Inline'));
        $this->registry->registerPreset('container', ['name' => 'Array Style']);

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(3);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('Test Preset From Class');
        expect($retrieved->presets[1]->toArray()['name'])->toBe('Inline');
    });

    it('registerPresets batch method works', function () {
        $schema = new BlockSchema('text', 'text', TestBlock::class, 'Text');
        $this->registry->register($schema);

        $this->registry->registerPresets('text', [
            TestPresetClass::class,
            AnotherTestPreset::class,
            BlockPreset::make('Callout'),
            ['name' => 'Highlight'],
        ]);

        $retrieved = $this->registry->get('text');
        expect($retrieved->presets)->toHaveCount(4);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('Test Preset From Class');
        expect($retrieved->presets[1]->toArray()['name'])->toBe('Another Test Preset');
        expect($retrieved->presets[2]->toArray()['name'])->toBe('Callout');
    });

    it('registerPresets works before schema registration', function () {
        $this->registry->registerPresets('text', [
            TestPresetClass::class,
            BlockPreset::make('Custom'),
        ]);

        $schema = new BlockSchema('text', 'text', TestBlock::class, 'Text');
        $this->registry->register($schema);

        $retrieved = $this->registry->get('text');
        expect($retrieved->presets)->toHaveCount(2);
    });

    it('custom presets append to block-defined presets', function () {
        $schema = new BlockSchema(
            type: 'container',
            slug: 'container',
            class: TestBlock::class,
            name: 'Container',
            presets: [
                BlockPreset::make('First Preset'),
                BlockPreset::make('Second Preset'),
            ]
        );
        $this->registry->register($schema);

        $this->registry->registerPreset('container', BlockPreset::make('Third Preset'));

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(3);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('First Preset');
        expect($retrieved->presets[1]->toArray()['name'])->toBe('Second Preset');
        expect($retrieved->presets[2]->toArray()['name'])->toBe('Third Preset');
    });

    it('can register complex presets with children', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $this->registry->registerPreset('container', BlockPreset::make('Hero Section')
            ->description('A hero section with heading and CTA')
            ->properties(['padding' => 40])
            ->blocks([
                PresetChild::make('text')->id('heading')->static(),
                PresetChild::make('button')->id('cta'),
            ]));

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(1);

        $preset = $retrieved->presets[0];
        $array = $preset->toArray();
        expect($array['name'])->toBe('Hero Section');
        expect($array['description'])->toBe('A hero section with heading and CTA');
        expect($array['children'])->toHaveCount(2);
        expect($array['children'][0]['type'])->toBe('text');
        expect($array['children'][0]['id'])->toBe('heading');
    });

    it('fluent interface works on block schema directly', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');

        $result = $schema->registerPreset(BlockPreset::make('First'))
            ->registerPreset(BlockPreset::make('Second'));

        expect($result)->toBe($schema);
        expect($schema->presets)->toHaveCount(2);
        expect($schema->presets[0]->toArray()['name'])->toBe('First');
        expect($schema->presets[1]->toArray()['name'])->toBe('Second');
    });

    it('registered schema modifications persist', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $this->registry->registerPreset('container', BlockPreset::make('Custom'));

        $retrieved1 = $this->registry->get('container');
        $retrieved2 = $this->registry->get('container');

        expect($retrieved1)->toBe($retrieved2);
        expect($retrieved1->presets)->toHaveCount(1);
        expect($retrieved2->presets)->toHaveCount(1);
    });

    it('pending presets are cleaned up after schema registration', function () {
        $this->registry->registerPreset('container', BlockPreset::make('Pending'));

        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $this->registry->registerPreset('container', BlockPreset::make('After'));

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(2);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('Pending');
        expect($retrieved->presets[1]->toArray()['name'])->toBe('After');
    });

    it('can register different presets to different block types', function () {
        $containerSchema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $textSchema = new BlockSchema('text', 'text', TestBlock::class, 'Text');

        $this->registry->register($containerSchema);
        $this->registry->register($textSchema);

        $this->registry->registerPreset('container', BlockPreset::make('Container Preset'));
        $this->registry->registerPreset('text', BlockPreset::make('Text Preset'));

        expect($this->registry->get('container')->presets)->toHaveCount(1);
        expect($this->registry->get('container')->presets[0]->toArray()['name'])->toBe('Container Preset');

        expect($this->registry->get('text')->presets)->toHaveCount(1);
        expect($this->registry->get('text')->presets[0]->toArray()['name'])->toBe('Text Preset');
    });

    it('handles empty presets array gracefully', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $this->registry->registerPresets('container', []);

        expect($this->registry->get('container')->presets)->toHaveCount(0);
    });
});
