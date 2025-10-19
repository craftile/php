<?php

use Craftile\Core\Data\BlockPreset;
use Craftile\Core\Data\PresetChild;

// Test preset with getType() override
class ProductCardPreset extends BlockPreset
{
    protected function getType(): string
    {
        return '@visual-debut/product-card';
    }

    protected function build(): void
    {
        $this
            ->name('Product Card')
            ->description('Product card with image and title')
            ->properties([
                'product' => null,
                'variant' => 'default',
            ])
            ->addBlocks([
                PresetChild::make('container')
                    ->addBlocks([
                        PresetChild::make('product-image')
                            ->properties(['size' => 'medium']),
                        PresetChild::make('product-title')
                            ->properties(['tag' => 'h3']),
                    ]),
            ]);
    }
}

// Test preset without getType() override
class GenericPreset extends BlockPreset
{
    protected function build(): void
    {
        $this
            ->name('Generic')
            ->properties(['color' => 'blue'])
            ->addBlocks([
                PresetChild::make('text'),
            ]);
    }
}

describe('BlockPreset asChild API', function () {
    it('converts preset to PresetChild using getType()', function () {
        $child = ProductCardPreset::asChild();

        expect($child)->toBeInstanceOf(PresetChild::class);

        $array = $child->toArray();

        expect($array['type'])->toBe('@visual-debut/product-card');
        expect($array['properties'])->toBe([
            'product' => null,
            'variant' => 'default',
        ]);
        expect($array['children'])->toHaveCount(1);
        expect($array['children'][0]['type'])->toBe('container');
    });

    it('allows overriding type via parameter', function () {
        $child = ProductCardPreset::asChild('@custom/product-card');

        $array = $child->toArray();

        expect($array['type'])->toBe('@custom/product-card');
        expect($array['properties'])->toBe([
            'product' => null,
            'variant' => 'default',
        ]);
    });

    it('throws exception when no type provided and getType() returns null', function () {
        GenericPreset::asChild();
    })->throws(
        \LogicException::class,
        'Type must be provided via asChild($type) or by overriding getType() method'
    );

    it('works when type is provided via parameter even if getType() is null', function () {
        $child = GenericPreset::asChild('generic-block');

        $array = $child->toArray();

        expect($array['type'])->toBe('generic-block');
        expect($array['properties'])->toBe(['color' => 'blue']);
        expect($array['children'])->toHaveCount(1);
    });

    it('allows chaining PresetChild modifiers after asChild()', function () {
        $child = ProductCardPreset::asChild()
            ->id('product-card-1')
            ->name('Featured Product')
            ->static()
            ->repeated()
            ->mergeProperties(['featured' => true]);

        $array = $child->toArray();

        expect($array['type'])->toBe('@visual-debut/product-card');
        expect($array['id'])->toBe('product-card-1');
        expect($array['name'])->toBe('Featured Product');
        expect($array['static'])->toBeTrue();
        expect($array['repeated'])->toBeTrue();
        expect($array['properties'])->toBe([
            'product' => null,
            'variant' => 'default',
            'featured' => true,  // Merged
        ]);
    });

    it('can be used in another preset\'s children', function () {
        $gridPreset = BlockPreset::make('Product Grid')
            ->addBlocks([
                PresetChild::make('container'),
                ProductCardPreset::asChild()
                    ->id('card-1')
                    ->static()
                    ->repeated(),
                PresetChild::make('footer'),
            ]);

        $array = $gridPreset->toArray();

        expect($array['children'])->toHaveCount(3);
        expect($array['children'][0]['type'])->toBe('container');
        expect($array['children'][1]['type'])->toBe('@visual-debut/product-card');
        expect($array['children'][1]['id'])->toBe('card-1');
        expect($array['children'][1]['static'])->toBeTrue();
        expect($array['children'][1]['repeated'])->toBeTrue();
        expect($array['children'][2]['type'])->toBe('footer');
    });

    it('preserves nested children structure', function () {
        $child = ProductCardPreset::asChild();

        $array = $child->toArray();

        // Check nested structure
        expect($array['children'])->toHaveCount(1);
        expect($array['children'][0]['type'])->toBe('container');
        expect($array['children'][0]['children'])->toHaveCount(2);
        expect($array['children'][0]['children'][0]['type'])->toBe('product-image');
        expect($array['children'][0]['children'][0]['properties']['size'])->toBe('medium');
        expect($array['children'][0]['children'][1]['type'])->toBe('product-title');
        expect($array['children'][0]['children'][1]['properties']['tag'])->toBe('h3');
    });

    it('can be used multiple times with different modifiers', function () {
        $preset = BlockPreset::make('Product List')
            ->addBlocks([
                ProductCardPreset::asChild()
                    ->id('featured-card')
                    ->mergeProperties(['featured' => true]),

                ProductCardPreset::asChild()
                    ->id('regular-card')
                    ->repeated(),
            ]);

        $array = $preset->toArray();

        expect($array['children'])->toHaveCount(2);

        // First instance
        expect($array['children'][0]['id'])->toBe('featured-card');
        expect($array['children'][0]['properties']['featured'])->toBeTrue();
        expect($array['children'][0])->not->toHaveKey('repeated');

        // Second instance
        expect($array['children'][1]['id'])->toBe('regular-card');
        expect($array['children'][1]['repeated'])->toBeTrue();
        expect($array['children'][1]['properties'])->not->toHaveKey('featured');
    });

    it('maintains independence between asChild() instances', function () {
        $child1 = ProductCardPreset::asChild()->mergeProperties(['size' => 'large']);
        $child2 = ProductCardPreset::asChild()->mergeProperties(['size' => 'small']);

        expect($child1->toArray()['properties']['size'])->toBe('large');
        expect($child2->toArray()['properties']['size'])->toBe('small');
    });

    it('copies name from preset to child', function () {
        $child = ProductCardPreset::asChild();

        $array = $child->toArray();

        expect($array['name'])->toBe('Product Card');
    });

    it('allows overriding name after asChild()', function () {
        $child = ProductCardPreset::asChild()
            ->name('Custom Product Card');

        $array = $child->toArray();

        expect($array['name'])->toBe('Custom Product Card');
    });
});
