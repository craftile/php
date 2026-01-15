<?php

use Craftile\Core\Data\BlockBuilder;
use Craftile\Core\Data\BlockPreset;
use Craftile\Core\Data\PresetChild;
use Craftile\Core\Data\Template;
use Tests\Laravel\Stubs\Discovery\StubTestBlock;

// Test preset class for use in PHP templates
class TestProductCardPreset extends BlockPreset
{
    public static function getType(): ?string
    {
        return 'product-card';
    }

    protected function build(): void
    {
        $this
            ->name('Product Card')
            ->properties([
                'product' => null,
                'variant' => 'default',
            ])
            ->addBlocks([
                PresetChild::make('product-image'),
                PresetChild::make('product-title'),
            ]);
    }
}

describe('Template fluent API', function () {
    it('creates empty template', function () {
        $template = Template::make()->toArray();

        expect($template)->toBe([]);
    });

    it('creates template with single block', function () {
        $template = Template::make()
            ->block('hero', 'hero', fn ($b) => $b
                ->properties(['title' => 'Welcome']))
            ->toArray();

        expect($template)->toHaveKey('blocks');
        expect($template['blocks'])->toHaveCount(1);
        expect($template['blocks']['hero']['type'])->toBe('hero');
        expect($template['blocks']['hero']['id'])->toBe('hero');
        expect($template['blocks']['hero']['properties'])->toBe(['title' => 'Welcome']);
    });

    it('creates template with multiple blocks', function () {
        $template = Template::make()
            ->block('header', 'header')
            ->block('hero', 'hero')
            ->block('footer', 'footer')
            ->toArray();

        expect($template['blocks'])->toHaveCount(3);
        expect($template['blocks']['header']['id'])->toBe('header');
        expect($template['blocks']['hero']['id'])->toBe('hero');
        expect($template['blocks']['footer']['id'])->toBe('footer');
    });

    it('supports blocks without callbacks', function () {
        $template = Template::make()
            ->block('header', 'header')
            ->block('footer', 'footer')
            ->toArray();

        expect($template['blocks'])->toHaveCount(2);
        expect($template['blocks']['header']['type'])->toBe('header');
        expect($template['blocks']['footer']['type'])->toBe('footer');
    });

    it('supports order at template level', function () {
        $template = Template::make()
            ->block('a', 'text')
            ->block('b', 'text')
            ->block('c', 'text')
            ->order(['c', 'a', 'b'])
            ->toArray();

        expect($template['order'])->toBe(['c', 'a', 'b']);
    });

    it('supports static blocks', function () {
        $template = Template::make()
            ->block('header', 'header', fn ($b) => $b->static())
            ->toArray();

        expect($template['blocks']['header']['static'])->toBeTrue();
    });

    it('supports repeated blocks', function () {
        $template = Template::make()
            ->block('card', 'product-card', fn ($b) => $b->repeated())
            ->toArray();

        expect($template['blocks']['card']['repeated'])->toBeTrue();
    });

    it('supports ghost blocks', function () {
        $template = Template::make()
            ->block('data', 'data-source', fn ($b) => $b->ghost())
            ->toArray();

        expect($template['blocks']['data']['ghost'])->toBeTrue();
    });

    it('supports custom names', function () {
        $template = Template::make()
            ->block('hero', 'hero', fn ($b) => $b->name('Custom Hero Name'))
            ->toArray();

        expect($template['blocks']['hero']['name'])->toBe('Custom Hero Name');
    });

    it('supports nested children', function () {
        $template = Template::make()
            ->block('container', 'container', fn ($b) => $b
                ->addBlocks([
                    PresetChild::make('text')->id('child-1'),
                    PresetChild::make('image')->id('child-2'),
                ]))
            ->toArray();

        expect($template['blocks']['container']['children'])->toHaveCount(2);
        expect($template['blocks']['container']['children'][0]['id'])->toBe('child-1');
        expect($template['blocks']['container']['children'][1]['id'])->toBe('child-2');
    });

    it('supports order for nested children', function () {
        $template = Template::make()
            ->block('container', 'container', fn ($b) => $b
                ->addBlocks([
                    PresetChild::make('text')->id('a'),
                    PresetChild::make('image')->id('b'),
                ])
                ->order(['b', 'a']))
            ->toArray();

        expect($template['blocks']['container']['order'])->toBe(['b', 'a']);
    });

    it('can be invoked to get array', function () {
        $template = Template::make()
            ->block('hero', 'hero');

        $result = $template();

        expect($result)->toBeArray();
        expect($result['blocks'])->toHaveCount(1);
    });

    it('supports full fluent chain', function () {
        $template = Template::make()
            ->block('header', 'header', fn ($b) => $b
                ->properties(['logo' => '/logo.svg'])
                ->static())
            ->block('hero', 'hero', fn ($b) => $b
                ->name('Hero Section')
                ->properties(['title' => 'Welcome', 'subtitle' => 'To our site'])
                ->addBlocks([
                    PresetChild::make('button')->id('cta')->properties(['label' => 'Get Started']),
                ]))
            ->block('products', 'grid', fn ($b) => $b
                ->addBlocks([
                    PresetChild::make('product-card')->id('card')->repeated(),
                ])
                ->order(['card']))
            ->order(['header', 'hero', 'products'])
            ->toArray();

        expect($template['blocks'])->toHaveCount(3);
        expect($template['order'])->toBe(['header', 'hero', 'products']);
        expect($template['blocks']['header']['static'])->toBeTrue();
        expect($template['blocks']['hero']['name'])->toBe('Hero Section');
        expect($template['blocks']['hero']['children'])->toHaveCount(1);
        expect($template['blocks']['products']['order'])->toBe(['card']);
    });

    it('supports multi-line callbacks', function () {
        $template = Template::make()
            ->block('container', 'container', function ($b) {
                $b->properties(['width' => '100%']);
                $b->static();

                return $b;
            })
            ->toArray();

        expect($template['blocks']['container']['properties']['width'])->toBe('100%');
        expect($template['blocks']['container']['static'])->toBeTrue();
    });
});

describe('BlockBuilder', function () {
    it('creates block with type string', function () {
        $block = BlockBuilder::forTemplate('hero', 'hero');

        expect($block)->toBeInstanceOf(BlockBuilder::class);
        expect($block->id)->toBe('hero');
        expect($block->type)->toBe('hero');
    });

    it('creates block from BlockPreset class', function () {
        $block = BlockBuilder::forTemplate('card', TestProductCardPreset::class);

        expect($block)->toBeInstanceOf(BlockBuilder::class);
        expect($block->id)->toBe('card');
        expect($block->type)->toBe('product-card');
        expect($block->properties)->toBe(['product' => null, 'variant' => 'default']);
        expect($block->name)->toBe('Product Card');
        expect($block->children)->toHaveCount(2);
    });

    it('creates block from BlockInterface class', function () {
        $block = BlockBuilder::forTemplate('test', StubTestBlock::class);

        expect($block)->toBeInstanceOf(BlockBuilder::class);
        expect($block->id)->toBe('test');
        expect($block->type)->toBe('stub-test-block');
    });

    it('supports fluent methods from PresetChild', function () {
        $block = BlockBuilder::forTemplate('hero', 'hero')
            ->properties(['title' => 'Hello'])
            ->static()
            ->repeated();

        expect($block->properties)->toBe(['title' => 'Hello']);
        expect($block->static)->toBeTrue();
        expect($block->repeated)->toBeTrue();
    });

    it('converts to array correctly', function () {
        $block = BlockBuilder::forTemplate('hero', 'hero')
            ->properties(['title' => 'Welcome'])
            ->static();

        $array = $block->toArray();

        expect($array['type'])->toBe('hero');
        expect($array['id'])->toBe('hero');
        expect($array['properties'])->toBe(['title' => 'Welcome']);
        expect($array['static'])->toBeTrue();
    });

    it('fromPresetChild preserves all properties', function () {
        $child = PresetChild::make('container')
            ->name('Container')
            ->properties(['width' => '100%'])
            ->static()
            ->ghost()
            ->repeated()
            ->addBlocks([
                PresetChild::make('text')->id('child'),
            ])
            ->order(['child']);

        $block = BlockBuilder::fromPresetChild($child, 'my-container');

        expect($block->id)->toBe('my-container');
        expect($block->type)->toBe('container');
        expect($block->name)->toBe('Container');
        expect($block->properties)->toBe(['width' => '100%']);
        expect($block->static)->toBeTrue();
        expect($block->ghost)->toBeTrue();
        expect($block->repeated)->toBeTrue();
        expect($block->children)->toHaveCount(1);
        expect($block->childrenOrder)->toBe(['child']);
    });
});

describe('PresetChild order() method', function () {
    it('sets children order', function () {
        $child = PresetChild::make('container')
            ->addBlocks([
                PresetChild::make('a')->id('a'),
                PresetChild::make('b')->id('b'),
            ])
            ->order(['b', 'a']);

        expect($child->childrenOrder)->toBe(['b', 'a']);
    });

    it('includes order in toArray when children exist', function () {
        $child = PresetChild::make('container')
            ->addBlocks([
                PresetChild::make('a')->id('a'),
                PresetChild::make('b')->id('b'),
            ])
            ->order(['b', 'a']);

        $array = $child->toArray();

        expect($array['order'])->toBe(['b', 'a']);
    });

    it('does not include order when no children', function () {
        $child = PresetChild::make('container')
            ->order(['b', 'a']);

        $array = $child->toArray();

        expect($array)->not->toHaveKey('order');
    });

    it('does not include order when childrenOrder is null', function () {
        $child = PresetChild::make('container')
            ->addBlocks([
                PresetChild::make('a')->id('a'),
            ]);

        $array = $child->toArray();

        expect($array)->not->toHaveKey('order');
    });

    it('supports fluent chaining with order()', function () {
        $child = PresetChild::make('container')
            ->properties(['width' => '100%'])
            ->addBlocks([
                PresetChild::make('a')->id('a'),
            ])
            ->order(['a'])
            ->static();

        expect($child->properties)->toBe(['width' => '100%']);
        expect($child->childrenOrder)->toBe(['a']);
        expect($child->static)->toBeTrue();
    });
});

describe('PHP template integration', function () {
    it('can use BlockPreset in Template', function () {
        $template = Template::make()
            ->block('card', TestProductCardPreset::class)
            ->toArray();

        expect($template['blocks']['card']['type'])->toBe('product-card');
        expect($template['blocks']['card']['id'])->toBe('card');
        expect($template['blocks']['card']['name'])->toBe('Product Card');
        expect($template['blocks']['card']['children'])->toHaveCount(2);
    });

    it('can use BlockInterface class in Template', function () {
        $template = Template::make()
            ->block('test', StubTestBlock::class)
            ->toArray();

        expect($template['blocks']['test']['type'])->toBe('stub-test-block');
        expect($template['blocks']['test']['id'])->toBe('test');
    });

    it('can customize BlockInterface class in Template', function () {
        $template = Template::make()
            ->block('test', StubTestBlock::class, fn ($b) => $b
                ->properties(['title' => 'Custom Title'])
                ->static())
            ->toArray();

        expect($template['blocks']['test']['type'])->toBe('stub-test-block');
        expect($template['blocks']['test']['properties'])->toBe(['title' => 'Custom Title']);
        expect($template['blocks']['test']['static'])->toBeTrue();
    });

    it('can customize BlockPreset in Template', function () {
        $template = Template::make()
            ->block('card', TestProductCardPreset::class, fn ($b) => $b
                ->mergeProperties(['featured' => true])
                ->static())
            ->toArray();

        expect($template['blocks']['card']['properties'])->toBe([
            'product' => null,
            'variant' => 'default',
            'featured' => true,
        ]);
        expect($template['blocks']['card']['static'])->toBeTrue();
    });

    it('demonstrates real-world usage', function () {
        $template = Template::make()
            ->block('header', 'header', fn ($b) => $b->static())
            ->block('hero', 'hero', fn ($b) => $b
                ->properties(['title' => 'Featured Products']))
            ->block('grid', 'container', fn ($b) => $b
                ->addBlocks([
                    // Use preset class
                    TestProductCardPreset::asChild()
                        ->id('featured')
                        ->mergeProperties(['featured' => true]),

                    // Use inline PresetChild
                    PresetChild::make('product-card')
                        ->id('regular')
                        ->repeated(),
                ])
                ->order(['featured', 'regular']))
            ->order(['header', 'hero', 'grid'])
            ->toArray();

        expect($template['blocks'])->toHaveCount(3);
        expect($template['order'])->toBe(['header', 'hero', 'grid']);
        expect($template['blocks']['grid']['children'])->toHaveCount(2);
        expect($template['blocks']['grid']['order'])->toBe(['featured', 'regular']);
        expect($template['blocks']['grid']['children'][0]['properties']['featured'])->toBeTrue();
    });
});

describe('Template region API', function () {
    it('supports region id and name', function () {
        $template = Template::make()
            ->id('header')
            ->name('Header')
            ->block('logo', 'logo')
            ->block('nav', 'navigation')
            ->toArray();

        expect($template['blocks'])->toHaveCount(2);
        expect($template['regions'])->toHaveCount(1);
        expect($template['regions'][0]['id'])->toBe('header');
        expect($template['regions'][0]['name'])->toBe('Header');
        expect($template['regions'][0]['blocks'])->toBe(['logo', 'nav']);
    });

    it('uses region id as name when name not provided', function () {
        $template = Template::make()
            ->id('footer')
            ->block('copyright', 'text')
            ->toArray();

        expect($template['regions'][0]['id'])->toBe('footer');
        expect($template['regions'][0]['name'])->toBe('footer');
        expect($template['regions'][0]['blocks'])->toBe(['copyright']);
    });

    it('respects block order in region', function () {
        $template = Template::make()
            ->id('header')
            ->name('Header')
            ->block('logo', 'logo')
            ->block('nav', 'navigation')
            ->block('search', 'search')
            ->order(['nav', 'search', 'logo'])
            ->toArray();

        expect($template['regions'][0]['blocks'])->toBe(['nav', 'search', 'logo']);
        expect($template['order'])->toBe(['nav', 'search', 'logo']);
    });

    it('does not include regions when region id not set', function () {
        $template = Template::make()
            ->block('hero', 'hero')
            ->toArray();

        expect($template)->not()->toHaveKey('regions');
    });
});
