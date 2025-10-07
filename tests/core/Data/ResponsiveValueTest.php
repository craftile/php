<?php

use Craftile\Core\Data\ResponsiveValue;

describe('ResponsiveValue', function () {
    it('can be created with breakpoint values', function () {
        $values = [
            '_default' => 'Mobile Title',
            'md' => 'Tablet Title',
            'lg' => 'Desktop Title',
        ];

        $responsive = new ResponsiveValue($values, 'text');

        expect($responsive->value())->toBe('Mobile Title');
        expect($responsive->getType())->toBe('text');
    });

    it('returns default value when accessed directly', function () {
        $values = [
            '_default' => 'Default Value',
            'md' => 'Medium Value',
        ];

        $responsive = new ResponsiveValue($values);

        expect($responsive->value())->toBe('Default Value');
    });

    it('can access breakpoint values via magic getter', function () {
        $values = [
            '_default' => 'Mobile',
            'sm' => 'Small',
            'md' => 'Medium',
            'lg' => 'Large',
            'xl' => 'Extra Large',
        ];

        $responsive = new ResponsiveValue($values);

        expect($responsive->sm)->toBe('Small');
        expect($responsive->md)->toBe('Medium');
        expect($responsive->lg)->toBe('Large');
        expect($responsive->xl)->toBe('Extra Large');
    });

    it('falls back to default when breakpoint not set', function () {
        $values = [
            '_default' => 'Default',
            'md' => 'Medium',
        ];

        $responsive = new ResponsiveValue($values);

        expect($responsive->sm)->toBe('Default'); // No sm value, falls back
        expect($responsive->lg)->toBe('Default'); // No lg value, falls back
        expect($responsive->xl)->toBe('Default'); // No xl value, falls back
        expect($responsive->md)->toBe('Medium'); // Has md value
    });

    it('converts to string using default value', function () {
        $values = [
            '_default' => 'Hello World',
            'md' => 'Hello Medium',
        ];

        $responsive = new ResponsiveValue($values);

        expect((string) $responsive)->toBe('Hello World');
    });

    it('can get all breakpoint values', function () {
        $values = [
            '_default' => 'A',
            'md' => 'B',
            'lg' => 'C',
        ];

        $responsive = new ResponsiveValue($values);

        expect($responsive->all())->toBe($values);
    });

    it('can check if breakpoint exists', function () {
        $values = [
            '_default' => 'Default',
            'md' => 'Medium',
            'lg' => 'Large',
        ];

        $responsive = new ResponsiveValue($values);

        expect($responsive->has('_default'))->toBeTrue();
        expect($responsive->has('md'))->toBeTrue();
        expect($responsive->has('lg'))->toBeTrue();
        expect($responsive->has('sm'))->toBeFalse();
        expect($responsive->has('xl'))->toBeFalse();
    });

    it('can get value with custom fallback', function () {
        $values = [
            '_default' => 'Default',
            'md' => 'Medium',
        ];

        $responsive = new ResponsiveValue($values);

        expect($responsive->get('md'))->toBe('Medium');
        expect($responsive->get('lg'))->toBe('Default'); // Falls back to _default
        expect($responsive->get('xl', 'Custom'))->toBe('Default'); // Still uses _default
    });

    it('works with numeric values', function () {
        $values = [
            '_default' => 1,
            'md' => 2,
            'lg' => 4,
            'xl' => 8,
        ];

        $responsive = new ResponsiveValue($values, 'number');

        expect($responsive->value())->toBe(1);
        expect($responsive->md)->toBe(2);
        expect($responsive->lg)->toBe(4);
        expect($responsive->xl)->toBe(8);
        expect($responsive->sm)->toBe(1); // Fallback
    });

    it('works with array values', function () {
        $values = [
            '_default' => ['option1', 'option2'],
            'md' => ['option3', 'option4'],
        ];

        $responsive = new ResponsiveValue($values);

        expect($responsive->value())->toBe(['option1', 'option2']);
        expect($responsive->md)->toBe(['option3', 'option4']);
    });

    it('handles null default value', function () {
        $values = [
            '_default' => null,
            'md' => 'Medium',
        ];

        $responsive = new ResponsiveValue($values);

        expect($responsive->value())->toBeNull();
        expect($responsive->sm)->toBeNull(); // Fallback to null
        expect($responsive->md)->toBe('Medium');
    });

    it('can be used in string interpolation', function () {
        $values = [
            '_default' => 'World',
            'md' => 'Universe',
        ];

        $responsive = new ResponsiveValue($values);

        $greeting = "Hello {$responsive}";

        expect($greeting)->toBe('Hello World');
    });
});
