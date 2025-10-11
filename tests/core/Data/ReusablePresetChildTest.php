<?php

use Craftile\Core\Data\BlockPreset;
use Craftile\Core\Data\PresetChild;

// Test reusable preset child classes
class HeadingPresetChild extends PresetChild
{
    protected function getType(): string
    {
        return 'heading';
    }

    protected function build(): void
    {
        $this->id('heading')
            ->properties(['level' => 2, 'text' => ''])
            ->static();
    }
}

class ParagraphPresetChild extends PresetChild
{
    protected function getType(): string
    {
        return 'paragraph';
    }

    protected function build(): void
    {
        $this->id('paragraph')
            ->properties(['text' => '']);
    }
}

class ButtonPresetChild extends PresetChild
{
    // Should derive "button" from class name (removes "PresetChild" suffix)

    protected function build(): void
    {
        $this->properties([
            'label' => 'Click me',
            'variant' => 'primary',
        ]);
    }
}

class TwoColumnContainerChild extends PresetChild
{
    // Should derive "two-column-container" from class name

    protected function build(): void
    {
        $this->id('container')
            ->properties(['gap' => 16])
            ->children([
                PresetChild::make('column')->properties(['width' => '50%']),
                PresetChild::make('column')->properties(['width' => '50%']),
            ]);
    }
}

class TypeOverrideInBuildChild extends PresetChild
{
    protected function getType(): string
    {
        return 'default-type';
    }

    protected function build(): void
    {
        $this->type('build-override-type')
            ->properties(['test' => true]);
    }
}

describe('Reusable PresetChild', function () {
    describe('PresetChild build() pattern', function () {
        it('can instantiate reusable preset child with default type from getType()', function () {
            $child = HeadingPresetChild::make();

            $array = $child->toArray();

            expect($array['type'])->toBe('heading');
            expect($array['id'])->toBe('heading');
            expect($array['properties']['level'])->toBe(2);
            expect($array['static'])->toBeTrue();
        });

        it('can instantiate reusable preset child with custom type', function () {
            $child = HeadingPresetChild::make('custom-heading');

            expect($child->toArray()['type'])->toBe('custom-heading');
            expect($child->toArray()['id'])->toBe('heading'); // build() still runs
        });

        it('can customize reusable preset child after instantiation', function () {
            $child = ParagraphPresetChild::make()
                ->mergeProperties(['fontSize' => 16])
                ->name('Custom Paragraph');

            $array = $child->toArray();

            expect($array['type'])->toBe('paragraph');
            expect($array['properties']['text'])->toBe('');
            expect($array['properties']['fontSize'])->toBe(16);
            expect($array['name'])->toBe('Custom Paragraph');
        });

        it('derives type from class name when no getType()', function () {
            $child = ButtonPresetChild::make();

            expect($child->toArray()['type'])->toBe('button');
        });

        it('derives kebab-case type from PascalCase class name', function () {
            $child = TwoColumnContainerChild::make();

            expect($child->toArray()['type'])->toBe('two-column-container');
        });

        it('removes PresetChild suffix from derived type', function () {
            $child = HeadingPresetChild::make();

            // Class is "HeadingPresetChild" but type should be "heading"
            expect($child->toArray()['type'])->toBe('heading');
        });

        it('removes Child suffix from derived type', function () {
            $child = TwoColumnContainerChild::make();

            // Class is "TwoColumnContainerChild" but type should be "two-column-container"
            expect($child->toArray()['type'])->toBe('two-column-container');
        });

        it('build() can override type from getType()', function () {
            $child = TypeOverrideInBuildChild::make();

            expect($child->toArray()['type'])->toBe('build-override-type');
        });

        it('constructor param overrides build() type', function () {
            $child = TypeOverrideInBuildChild::make('constructor-type');

            expect($child->toArray()['type'])->toBe('constructor-type');
        });

        it('type() setter can override after instantiation', function () {
            $child = HeadingPresetChild::make()
                ->type('after-override');

            expect($child->toArray()['type'])->toBe('after-override');
        });
    });

    describe('BlockPreset child normalization', function () {
        it('normalizes child class reference to instance', function () {
            $preset = BlockPreset::make('Test')
                ->blocks([
                    HeadingPresetChild::class,
                    ParagraphPresetChild::class,
                ]);

            $array = $preset->toArray();

            expect($array['children'])->toHaveCount(2);
            expect($array['children'][0]['type'])->toBe('heading');
            expect($array['children'][0]['id'])->toBe('heading');
            expect($array['children'][1]['type'])->toBe('paragraph');
            expect($array['children'][1]['id'])->toBe('paragraph');
        });

        it('supports mixed child types (classes, instances, arrays)', function () {
            $preset = BlockPreset::make('Test')
                ->blocks([
                    HeadingPresetChild::class,  // Class reference
                    PresetChild::make('text')->id('inline'),  // Instance
                    ['type' => 'image', 'id' => 'array'],  // Array
                ]);

            $array = $preset->toArray();

            expect($array['children'])->toHaveCount(3);
            expect($array['children'][0]['type'])->toBe('heading');
            expect($array['children'][1]['type'])->toBe('text');
            expect($array['children'][1]['id'])->toBe('inline');
            expect($array['children'][2])->toBe(['type' => 'image', 'id' => 'array']);
        });

        it('normalizes children when using addBlock', function () {
            $preset = BlockPreset::make('Test')
                ->addBlock(HeadingPresetChild::class);

            $array = $preset->toArray();

            expect($array['children'])->toHaveCount(1);
            expect($array['children'][0]['type'])->toBe('heading');
        });

        it('normalizes children when using addBlocks', function () {
            $preset = BlockPreset::make('Test')
                ->addBlocks([
                    HeadingPresetChild::class,
                    ParagraphPresetChild::class,
                ]);

            $array = $preset->toArray();

            expect($array['children'])->toHaveCount(2);
            expect($array['children'][0]['type'])->toBe('heading');
            expect($array['children'][1]['type'])->toBe('paragraph');
        });

        it('ignores non-preset-child class strings', function () {
            $preset = BlockPreset::make('Test')
                ->blocks([
                    'NotAClass',  // Non-existent class
                    BlockPreset::class,  // Not a PresetChild class
                ]);

            $array = $preset->toArray();

            // Both should be returned as-is since they don't extend PresetChild
            expect($array['children'][0])->toBe('NotAClass');
            expect($array['children'][1])->toBe(BlockPreset::class);
        });
    });

    describe('PresetChild child normalization (nested)', function () {
        it('normalizes nested child class references', function () {
            $parent = PresetChild::make('container')
                ->children([
                    HeadingPresetChild::class,
                    ParagraphPresetChild::class,
                ]);

            $array = $parent->toArray();

            expect($array['children'])->toHaveCount(2);
            expect($array['children'][0]['type'])->toBe('heading');
            expect($array['children'][1]['type'])->toBe('paragraph');
        });

        it('supports mixed nested child types', function () {
            $parent = PresetChild::make('container')
                ->children([
                    HeadingPresetChild::class,
                    PresetChild::make('text'),
                    ['type' => 'image'],
                ]);

            $array = $parent->toArray();

            expect($array['children'])->toHaveCount(3);
            expect($array['children'][0]['type'])->toBe('heading');
            expect($array['children'][1]['type'])->toBe('text');
            expect($array['children'][2])->toBe(['type' => 'image']);
        });

        it('normalizes when using addChild', function () {
            $parent = PresetChild::make('container')
                ->addChild(HeadingPresetChild::class);

            $array = $parent->toArray();

            expect($array['children'])->toHaveCount(1);
            expect($array['children'][0]['type'])->toBe('heading');
        });

        it('normalizes when using addChildren', function () {
            $parent = PresetChild::make('container')
                ->addChildren([
                    HeadingPresetChild::class,
                    ParagraphPresetChild::class,
                ]);

            $array = $parent->toArray();

            expect($array['children'])->toHaveCount(2);
        });

        it('normalizes when using addBlock alias', function () {
            $parent = PresetChild::make('container')
                ->addBlock(HeadingPresetChild::class);

            $array = $parent->toArray();

            expect($array['children'])->toHaveCount(1);
            expect($array['children'][0]['type'])->toBe('heading');
        });

        it('normalizes when using addBlocks alias', function () {
            $parent = PresetChild::make('container')
                ->addBlocks([
                    HeadingPresetChild::class,
                    ParagraphPresetChild::class,
                ]);

            $array = $parent->toArray();

            expect($array['children'])->toHaveCount(2);
        });
    });

    describe('Reusable preset child with nested children', function () {
        it('can define preset child with nested children in build()', function () {
            $container = TwoColumnContainerChild::make();

            $array = $container->toArray();

            expect($array['type'])->toBe('two-column-container');
            expect($array['id'])->toBe('container');
            expect($array['properties']['gap'])->toBe(16);
            expect($array['children'])->toHaveCount(2);
            expect($array['children'][0]['type'])->toBe('column');
            expect($array['children'][1]['type'])->toBe('column');
        });

        it('can use preset child classes as nested children', function () {
            $parent = PresetChild::make('section')
                ->children([
                    TwoColumnContainerChild::class,
                ]);

            $array = $parent->toArray();

            expect($array['children'])->toHaveCount(1);
            expect($array['children'][0]['type'])->toBe('two-column-container');
            expect($array['children'][0]['children'])->toHaveCount(2);
        });
    });

    describe('Usage patterns with BlockPreset', function () {
        it('demonstrates default usage in BlockPreset build()', function () {
            $preset = new class extends BlockPreset
            {
                protected function getName(): string
                {
                    return 'Test Preset';
                }

                protected function build(): void
                {
                    $this->blocks([
                        HeadingPresetChild::class,
                        ParagraphPresetChild::class,
                    ]);
                }
            };

            $instance = $preset::make();
            $array = $instance->toArray();

            expect($array['children'])->toHaveCount(2);
            expect($array['children'][0]['type'])->toBe('heading');
            expect($array['children'][1]['type'])->toBe('paragraph');
        });

        it('demonstrates customization in BlockPreset build()', function () {
            $preset = new class extends BlockPreset
            {
                protected function getName(): string
                {
                    return 'Test Preset';
                }

                protected function build(): void
                {
                    $this->blocks([
                        HeadingPresetChild::make()->mergeProperties(['level' => 3]),
                        ParagraphPresetChild::make()->id('custom-id'),
                    ]);
                }
            };

            $instance = $preset::make();
            $array = $instance->toArray();

            expect($array['children'][0]['properties']['level'])->toBe(3);
            expect($array['children'][1]['id'])->toBe('custom-id');
        });

        it('demonstrates mixed usage', function () {
            $preset = new class extends BlockPreset
            {
                protected function getName(): string
                {
                    return 'Test Preset';
                }

                protected function build(): void
                {
                    $this->blocks([
                        HeadingPresetChild::class,  // Default
                        ParagraphPresetChild::make()->static(),  // Customized
                        PresetChild::make('button'),  // Direct instance
                        ['type' => 'image', 'id' => 'hero'],  // Array
                    ]);
                }
            };

            $instance = $preset::make();
            $array = $instance->toArray();

            expect($array['children'])->toHaveCount(4);
            expect($array['children'][0]['type'])->toBe('heading');
            expect($array['children'][1]['static'])->toBeTrue();
            expect($array['children'][2]['type'])->toBe('button');
            expect($array['children'][3])->toBe(['type' => 'image', 'id' => 'hero']);
        });
    });

    describe('Backward compatibility', function () {
        it('still works with direct PresetChild::make(type) usage', function () {
            $child = PresetChild::make('text')
                ->id('test')
                ->properties(['content' => 'Hello']);

            $array = $child->toArray();

            expect($array['type'])->toBe('text');
            expect($array['id'])->toBe('test');
            expect($array['properties']['content'])->toBe('Hello');
        });

        it('still works with array children in presets', function () {
            $preset = BlockPreset::make('Test')
                ->blocks([
                    ['type' => 'text', 'id' => 'heading'],
                    ['type' => 'image', 'id' => 'banner'],
                ]);

            $array = $preset->toArray();

            expect($array['children'])->toHaveCount(2);
            expect($array['children'][0])->toBe(['type' => 'text', 'id' => 'heading']);
            expect($array['children'][1])->toBe(['type' => 'image', 'id' => 'banner']);
        });
    });
});
