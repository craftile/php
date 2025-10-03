<?php

use Craftile\Core\Data\Property;

class TestProperty extends Property
{
    public function type(): string
    {
        return 'test';
    }
}

test('can create property with key and label', function () {
    $property = new TestProperty('title', 'Title');

    expect($property->id)->toBe('title');
    expect($property->label)->toBe('Title');
    expect($property->meta)->toBe([]);
});

test('can create property using make method', function () {
    $property = TestProperty::make('description', 'Description');

    expect($property->id)->toBe('description');
    expect($property->label)->toBe('Description');
});

test('can set default value', function () {
    $property = TestProperty::make('color', 'Color')
        ->default('#ffffff');

    expect($property->meta['default'])->toBe('#ffffff');
});

test('can set placeholder', function () {
    $property = TestProperty::make('email', 'Email')
        ->placeholder('Enter your email');

    expect($property->meta['placeholder'])->toBe('Enter your email');
});

test('can set info text', function () {
    $property = TestProperty::make('password', 'Password')
        ->info('Must be at least 8 characters');

    expect($property->meta['info'])->toBe('Must be at least 8 characters');
});

test('can chain multiple meta methods', function () {
    $property = TestProperty::make('age', 'Age')
        ->default(18)
        ->placeholder('Enter age')
        ->info('Must be 18 or older');

    expect($property->meta['default'])->toBe(18);
    expect($property->meta['placeholder'])->toBe('Enter age');
    expect($property->meta['info'])->toBe('Must be 18 or older');
});

test('can serialize to array', function () {
    $property = TestProperty::make('username', 'Username')
        ->default('user123');

    $array = $property->toArray();

    expect($array['id'])->toBe('username');
    expect($array['type'])->toBe('test');
    expect($array['label'])->toBe('Username');
    expect($array['default'])->toBe('user123');
});
