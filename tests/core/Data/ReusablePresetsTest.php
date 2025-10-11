<?php

use Craftile\Core\Contracts\BlockInterface;
use Craftile\Core\Data\BlockPreset;
use Craftile\Core\Data\BlockSchema;
use Craftile\Core\Data\PresetChild;

// Test reusable preset classes
class RichTextPreset extends BlockPreset
{
    protected function getName(): string
    {
        return 'Rich Text Editor';
    }

    protected function build(): void
    {
        $this->description('Rich text editor with formatting options')
            ->icon('<svg>rich-text-icon</svg>')
            ->properties([
                'placeholder' => 'Enter text...',
            ])
            ->blocks([
                PresetChild::make('paragraph')
                    ->id('paragraph')
                    ->properties(['text' => '']),
                PresetChild::make('heading')
                    ->id('heading')
                    ->properties(['level' => 2, 'text' => '']),
            ]);
    }
}

class LayoutPreset extends BlockPreset
{
    // No getName() override - should derive "Layout" from class name

    protected function build(): void
    {
        $this->description('Common layout pattern')
            ->blocks([
                PresetChild::make('container')
                    ->id('wrapper')
                    ->properties(['padding' => 20]),
            ]);
    }
}

class TwoColumnLayoutPreset extends BlockPreset
{
    // Should derive "Two Column Layout" from class name

    protected function build(): void
    {
        $this->description('Two column layout with sidebar')
            ->properties(['gap' => 16])
            ->blocks([
                PresetChild::make('column')
                    ->id('main')
                    ->properties(['width' => '70%']),
                PresetChild::make('column')
                    ->id('sidebar')
                    ->properties(['width' => '30%']),
            ]);
    }
}

class NameOverrideInBuildPreset extends BlockPreset
{
    protected function getName(): string
    {
        return 'Default Name';
    }

    protected function build(): void
    {
        $this->name('Build Override Name')
            ->description('Name set in build()');
    }
}

// Test block class
class TestBlockWithPresetClasses implements BlockInterface
{
    use \Craftile\Core\Concerns\IsBlock;

    protected static string $type = 'test/preset-block';

    protected static array $presets = [
        RichTextPreset::class,
        LayoutPreset::class,
    ];

    public function render(): mixed
    {
        return '';
    }
}

describe('Reusable Block Presets', function () {
    describe('BlockPreset fluent API', function () {
        it('can append single block', function () {
            $preset = BlockPreset::make()
                ->blocks([PresetChild::make('text')])
                ->addBlock(PresetChild::make('image'));

            $array = $preset->toArray();

            expect($array['children'])->toHaveCount(2);
            expect($array['children'][0]['type'])->toBe('text');
            expect($array['children'][1]['type'])->toBe('image');
        });

        it('can append multiple blocks', function () {
            $preset = BlockPreset::make()
                ->blocks([PresetChild::make('text')])
                ->addBlocks([
                    PresetChild::make('image'),
                    PresetChild::make('video'),
                ]);

            $array = $preset->toArray();

            expect($array['children'])->toHaveCount(3);
            expect($array['children'][0]['type'])->toBe('text');
            expect($array['children'][1]['type'])->toBe('image');
            expect($array['children'][2]['type'])->toBe('video');
        });

        it('can merge properties', function () {
            $preset = BlockPreset::make()
                ->properties(['color' => 'blue', 'size' => 'large'])
                ->mergeProperties(['size' => 'small', 'weight' => 'bold']);

            $array = $preset->toArray();

            expect($array['properties'])->toBe([
                'color' => 'blue',
                'size' => 'small',  // Overridden
                'weight' => 'bold',  // Added
            ]);
        });

        it('supports full fluent chain', function () {
            $preset = BlockPreset::make('Fluent Test')
                ->description('Test description')
                ->properties(['gap' => 12])
                ->addBlock(PresetChild::make('text'))
                ->mergeProperties(['padding' => 16])
                ->addBlocks([
                    PresetChild::make('image'),
                    PresetChild::make('button'),
                ]);

            $array = $preset->toArray();

            expect($array['name'])->toBe('Fluent Test');
            expect($array['description'])->toBe('Test description');
            expect($array['properties'])->toBe(['gap' => 12, 'padding' => 16]);
            expect($array['children'])->toHaveCount(3);
        });
    });

    describe('PresetChild fluent API', function () {
        it('can append single child', function () {
            $child = PresetChild::make('container')
                ->children([PresetChild::make('text')])
                ->addChild(PresetChild::make('image'));

            $array = $child->toArray();

            expect($array['children'])->toHaveCount(2);
            expect($array['children'][0]['type'])->toBe('text');
            expect($array['children'][1]['type'])->toBe('image');
        });

        it('can append single child using addBlock alias', function () {
            $child = PresetChild::make('container')
                ->children([PresetChild::make('text')])
                ->addBlock(PresetChild::make('image'));

            $array = $child->toArray();

            expect($array['children'])->toHaveCount(2);
        });

        it('can append multiple children', function () {
            $child = PresetChild::make('container')
                ->children([PresetChild::make('text')])
                ->addChildren([
                    PresetChild::make('image'),
                    PresetChild::make('video'),
                ]);

            $array = $child->toArray();

            expect($array['children'])->toHaveCount(3);
        });

        it('can append multiple children using addBlocks alias', function () {
            $child = PresetChild::make('container')
                ->children([PresetChild::make('text')])
                ->addBlocks([
                    PresetChild::make('image'),
                    PresetChild::make('video'),
                ]);

            $array = $child->toArray();

            expect($array['children'])->toHaveCount(3);
        });

        it('can merge properties', function () {
            $child = PresetChild::make('text')
                ->properties(['color' => 'blue', 'size' => 'large'])
                ->mergeProperties(['size' => 'small', 'weight' => 'bold']);

            $array = $child->toArray();

            expect($array['properties'])->toBe([
                'color' => 'blue',
                'size' => 'small',
                'weight' => 'bold',
            ]);
        });
    });

    describe('Reusable preset classes', function () {
        it('can instantiate reusable preset with default name from getName()', function () {
            $preset = RichTextPreset::make();

            $array = $preset->toArray();

            expect($array['name'])->toBe('Rich Text Editor');
            expect($array['description'])->toBe('Rich text editor with formatting options');
            expect($array['icon'])->toBe('<svg>rich-text-icon</svg>');
            expect($array['properties'])->toHaveKey('placeholder');
            expect($array['children'])->toHaveCount(2);
        });

        it('can instantiate reusable preset with custom name', function () {
            $preset = RichTextPreset::make('Custom Name');

            expect($preset->toArray()['name'])->toBe('Custom Name');
        });

        it('can customize reusable preset after instantiation', function () {
            $preset = RichTextPreset::make()
                ->mergeProperties(['theme' => 'dark'])
                ->addBlock(PresetChild::make('image')->id('image'));

            $array = $preset->toArray();

            expect($array['properties'])->toHaveKey('placeholder');
            expect($array['properties'])->toHaveKey('theme');
            expect($array['children'])->toHaveCount(3);
            expect($array['children'][2]['type'])->toBe('image');
        });

        it('derives preset name from class name when no getName()', function () {
            $preset = LayoutPreset::make();

            expect($preset->toArray()['name'])->toBe('Layout');
        });

        it('derives preset name from PascalCase class name', function () {
            $preset = TwoColumnLayoutPreset::make();

            expect($preset->toArray()['name'])->toBe('Two Column Layout');
        });

        it('removes Preset suffix from derived name', function () {
            $preset = LayoutPreset::make();

            // Class is "LayoutPreset" but name should be "Layout"
            expect($preset->toArray()['name'])->toBe('Layout');
        });

        it('build() can override name from getName()', function () {
            $preset = NameOverrideInBuildPreset::make();

            expect($preset->toArray()['name'])->toBe('Build Override Name');
        });

        it('constructor param overrides build() name', function () {
            $preset = NameOverrideInBuildPreset::make('Constructor Name');

            expect($preset->toArray()['name'])->toBe('Constructor Name');
        });

        it('name() setter can override after instantiation', function () {
            $preset = RichTextPreset::make()
                ->name('After Override');

            expect($preset->toArray()['name'])->toBe('After Override');
        });
    });

    describe('BlockSchema preset normalization', function () {
        it('normalizes preset class reference to instance', function () {
            $schema = BlockSchema::fromClass(TestBlockWithPresetClasses::class);

            expect($schema->presets)->toHaveCount(2);
            expect($schema->presets[0])->toBeInstanceOf(BlockPreset::class);
            expect($schema->presets[1])->toBeInstanceOf(BlockPreset::class);
        });

        it('preset instances have correct data after normalization', function () {
            $schema = BlockSchema::fromClass(TestBlockWithPresetClasses::class);

            $richTextPreset = $schema->presets[0]->toArray();
            expect($richTextPreset['name'])->toBe('Rich Text Editor');
            expect($richTextPreset['description'])->toBe('Rich text editor with formatting options');
            expect($richTextPreset['children'])->toHaveCount(2);

            $layoutPreset = $schema->presets[1]->toArray();
            expect($layoutPreset['name'])->toBe('Layout');
            expect($layoutPreset['description'])->toBe('Common layout pattern');
        });

        it('supports mixed preset types (classes, instances, arrays)', function () {
            $schema = new BlockSchema(
                type: 'test/mixed',
                slug: 'test-mixed',
                class: TestBlockWithPresetClasses::class,
                name: 'Mixed Presets Block',
                presets: [
                    RichTextPreset::class,  // Class reference
                    BlockPreset::make('Inline')->properties(['gap' => 8]),  // Instance
                    ['name' => 'Array Preset', 'properties' => ['padding' => 12]],  // Array
                ]
            );

            expect($schema->presets)->toHaveCount(3);
            expect($schema->presets[0])->toBeInstanceOf(BlockPreset::class);
            expect($schema->presets[0]->toArray()['name'])->toBe('Rich Text Editor');
            expect($schema->presets[1])->toBeInstanceOf(BlockPreset::class);
            expect($schema->presets[1]->toArray()['name'])->toBe('Inline');
            expect($schema->presets[2])->toBe(['name' => 'Array Preset', 'properties' => ['padding' => 12]]);
        });

        it('handles empty presets array', function () {
            $schema = new BlockSchema(
                type: 'test/empty',
                slug: 'test-empty',
                class: TestBlockWithPresetClasses::class,
                name: 'Empty Presets Block',
                presets: []
            );

            expect($schema->presets)->toBeEmpty();
        });

        it('ignores non-preset class strings', function () {
            $schema = new BlockSchema(
                type: 'test/invalid',
                slug: 'test-invalid',
                class: TestBlockWithPresetClasses::class,
                name: 'Invalid Presets Block',
                presets: [
                    'NotAClass',  // Non-existent class
                    TestBlockWithPresetClasses::class,  // Not a preset class
                ]
            );

            // Both should be returned as-is since they don't extend BlockPreset
            expect($schema->presets[0])->toBe('NotAClass');
            expect($schema->presets[1])->toBe(TestBlockWithPresetClasses::class);
        });
    });

    describe('Usage patterns', function () {
        it('demonstrates default usage pattern', function () {
            // Usage: Just reference the class
            $schema = new BlockSchema(
                type: 'test/usage1',
                slug: 'test-usage1',
                class: TestBlockWithPresetClasses::class,
                name: 'Test Block',
                presets: [
                    RichTextPreset::class,  // Uses default configuration
                ]
            );

            $preset = $schema->presets[0];
            expect($preset->toArray()['name'])->toBe('Rich Text Editor');
            expect($preset->toArray()['children'])->toHaveCount(2);
        });

        it('demonstrates customization pattern', function () {
            // Usage: Instantiate and customize
            $schema = new BlockSchema(
                type: 'test/usage2',
                slug: 'test-usage2',
                class: TestBlockWithPresetClasses::class,
                name: 'Test Block',
                presets: [
                    RichTextPreset::make()
                        ->mergeProperties(['theme' => 'dark', 'placeholder' => 'Custom placeholder'])
                        ->addBlock(PresetChild::make('code')->id('code-block')),
                ]
            );

            $preset = $schema->presets[0];
            $array = $preset->toArray();

            expect($array['properties']['theme'])->toBe('dark');
            expect($array['properties']['placeholder'])->toBe('Custom placeholder');
            expect($array['children'])->toHaveCount(3);
            expect($array['children'][2]['type'])->toBe('code');
        });

        it('demonstrates custom name pattern', function () {
            // Usage: Custom name with reusable configuration
            $schema = new BlockSchema(
                type: 'test/usage3',
                slug: 'test-usage3',
                class: TestBlockWithPresetClasses::class,
                name: 'Test Block',
                presets: [
                    RichTextPreset::make('Blog Post Editor')
                        ->addBlocks([
                            PresetChild::make('image')->id('featured-image'),
                            PresetChild::make('metadata')->id('post-meta'),
                        ]),
                ]
            );

            $preset = $schema->presets[0];
            $array = $preset->toArray();

            expect($array['name'])->toBe('Blog Post Editor');
            expect($array['children'])->toHaveCount(4);
        });
    });
});
