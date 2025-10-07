<?php

use Craftile\Core\Data\Property;
use Craftile\Core\Data\PropertyBag;
use Craftile\Core\Data\ResponsiveValue;

class TestResponsiveProperty extends Property
{
    public function type(): string
    {
        return 'test';
    }
}

describe('Responsive Properties', function () {
    it('marks property as responsive', function () {
        $property = TestResponsiveProperty::make('title', 'Title')->responsive();

        $array = $property->toArray();

        expect($array['responsive'])->toBeTrue();
    });

    it('includes responsive flag in property schema', function () {
        $property = TestResponsiveProperty::make('columns', 'Columns')
            ->responsive()
            ->default(1);

        $array = $property->toArray();

        expect($array)->toHaveKey('responsive');
        expect($array['responsive'])->toBeTrue();
        expect($array['default'])->toBe(1);
    });

    it('transforms responsive values in PropertyBag', function () {
        $values = [
            'title' => [
                '_default' => 'Mobile Title',
                'md' => 'Tablet Title',
                'lg' => 'Desktop Title',
            ],
        ];

        $schemas = [
            'title' => [
                'type' => 'text',
                'responsive' => true,
            ],
        ];

        $bag = new PropertyBag($values, $schemas);

        $title = $bag->get('title');

        expect($title)->toBeInstanceOf(ResponsiveValue::class);
        expect($title->value())->toBe('Mobile Title');
        expect($title->md)->toBe('Tablet Title');
        expect($title->lg)->toBe('Desktop Title');
    });

    it('does not transform non-responsive array values', function () {
        $values = [
            'options' => ['option1', 'option2', 'option3'],
        ];

        $schemas = [
            'options' => [
                'type' => 'array',
                'responsive' => false,
            ],
        ];

        $bag = new PropertyBag($values, $schemas);

        $options = $bag->get('options');

        expect($options)->toBeArray();
        expect($options)->toBe(['option1', 'option2', 'option3']);
    });

    it('handles mixed responsive and non-responsive properties', function () {
        $values = [
            'title' => [
                '_default' => 'Default',
                'lg' => 'Large',
            ],
            'subtitle' => 'Regular String',
            'count' => 42,
        ];

        $schemas = [
            'title' => ['type' => 'text', 'responsive' => true],
            'subtitle' => ['type' => 'text', 'responsive' => false],
            'count' => ['type' => 'number', 'responsive' => false],
        ];

        $bag = new PropertyBag($values, $schemas);

        expect($bag->get('title'))->toBeInstanceOf(ResponsiveValue::class);
        expect($bag->get('title')->value())->toBe('Default');
        expect($bag->get('subtitle'))->toBe('Regular String');
        expect($bag->get('count'))->toBe(42);
    });

    it('requires _default key to be treated as responsive', function () {
        $values = [
            'colors' => [
                'primary' => 'blue',
                'secondary' => 'green',
            ],
        ];

        $schemas = [
            'colors' => [
                'type' => 'object',
                'responsive' => true, // Marked responsive but no _default
            ],
        ];

        $bag = new PropertyBag($values, $schemas);

        // Should NOT be treated as responsive because no _default key
        $colors = $bag->get('colors');
        expect($colors)->not->toBeInstanceOf(ResponsiveValue::class);
        expect($colors)->toBeArray();
    });

    it('caches resolved responsive values', function () {
        $values = [
            'title' => [
                '_default' => 'Default',
                'md' => 'Medium',
            ],
        ];

        $schemas = [
            'title' => ['type' => 'text', 'responsive' => true],
        ];

        $bag = new PropertyBag($values, $schemas);

        $first = $bag->get('title');
        $second = $bag->get('title');

        expect($first)->toBe($second); // Same instance
        expect($first)->toBeInstanceOf(ResponsiveValue::class);
    });

    it('works with numeric responsive values', function () {
        $values = [
            'columns' => [
                '_default' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ],
        ];

        $schemas = [
            'columns' => ['type' => 'number', 'responsive' => true],
        ];

        $bag = new PropertyBag($values, $schemas);

        $columns = $bag->get('columns');

        expect($columns)->toBeInstanceOf(ResponsiveValue::class);
        expect($columns->value())->toBe(1);
        expect($columns->md)->toBe(2);
        expect($columns->lg)->toBe(3);
        expect($columns->xl)->toBe(4);
    });

    it('allows direct property access via magic getter', function () {
        $values = [
            'heading' => [
                '_default' => 'Welcome',
                'lg' => 'Welcome to our site',
            ],
        ];

        $schemas = [
            'heading' => ['type' => 'text', 'responsive' => true],
        ];

        $bag = new PropertyBag($values, $schemas);

        expect($bag->heading)->toBeInstanceOf(ResponsiveValue::class);
        expect($bag->heading->value())->toBe('Welcome');
        expect($bag->heading->lg)->toBe('Welcome to our site');
    });

    it('supports property chaining', function () {
        $property = TestResponsiveProperty::make('padding', 'Padding')
            ->responsive()
            ->default(4);

        expect($property->meta['responsive'])->toBeTrue();
        expect($property->meta['default'])->toBe(4);
    });

    it('handles responsive values without schema', function () {
        $values = [
            'data' => [
                '_default' => 'Default',
                'md' => 'Medium',
            ],
        ];

        $bag = new PropertyBag($values, []); // No schemas

        // Should NOT transform to ResponsiveValue without schema
        $data = $bag->get('data');
        expect($data)->not->toBeInstanceOf(ResponsiveValue::class);
        expect($data)->toBeArray();
    });
});
