<?php

use Craftile\Core\Data\Property;

// Create a concrete test property class
class TestVisibleIfProperty extends Property
{
    public function type(): string
    {
        return 'test';
    }
}

test('property can have visibleIf condition', function () {
    $property = TestVisibleIfProperty::make('test_field', 'Test Field')
        ->visibleIf(fn ($rule) => $rule->where('layout', 'grid'));

    $array = $property->toArray();

    expect($array)->toHaveKey('visibleIf');
    expect($array['visibleIf'])->toBe([
        'field' => 'layout',
        'operator' => 'equals',
        'value' => 'grid',
    ]);
});

test('property visibleIf with multiple conditions', function () {
    $property = TestVisibleIfProperty::make('test_field', 'Test Field')
        ->visibleIf(fn ($rule) => $rule->where('layout', 'grid')
            ->where('status', 'published')
        );

    $array = $property->toArray();

    expect($array['visibleIf'])->toHaveKey('and');
    expect($array['visibleIf']['and'])->toHaveCount(2);
});

test('property visibleIf with in operator', function () {
    $property = TestVisibleIfProperty::make('test_field', 'Test Field')
        ->visibleIf(fn ($rule) => $rule->whereIn('layout', ['grid', 'flex']));

    $array = $property->toArray();

    expect($array['visibleIf'])->toBe([
        'field' => 'layout',
        'operator' => 'in',
        'value' => ['grid', 'flex'],
    ]);
});

test('property visibleIf with nested logic', function () {
    $property = TestVisibleIfProperty::make('test_field', 'Test Field')
        ->visibleIf(fn ($rule) => $rule->where('layout', 'grid')
            ->or(fn ($r) => $r->where('type', 'image'))
        );

    $array = $property->toArray();

    expect($array['visibleIf'])->toHaveKey('or');
});

test('property visibleIf with truthy condition', function () {
    $property = TestVisibleIfProperty::make('test_field', 'Test Field')
        ->visibleIf(fn ($rule) => $rule->whereTruthy('is_active'));

    $array = $property->toArray();

    expect($array['visibleIf'])->toBe([
        'field' => 'is_active',
        'operator' => 'truthy',
    ]);
});

test('property visibleIf can be chained with other methods', function () {
    $property = TestVisibleIfProperty::make('test_field', 'Test Field')
        ->default('default value')
        ->placeholder('Enter value')
        ->visibleIf(fn ($rule) => $rule->where('layout', 'grid'))
        ->info('Some info');

    $array = $property->toArray();

    expect($array)->toHaveKey('default', 'default value');
    expect($array)->toHaveKey('placeholder', 'Enter value');
    expect($array)->toHaveKey('visibleIf');
    expect($array)->toHaveKey('info', 'Some info');
});

test('property without visibleIf has no visibleIf key', function () {
    $property = TestVisibleIfProperty::make('test_field', 'Test Field')
        ->default('value');

    $array = $property->toArray();

    expect($array)->not->toHaveKey('visibleIf');
});

test('property toArray includes all metadata', function () {
    $property = TestVisibleIfProperty::make('columns', 'Number of Columns')
        ->default('3')
        ->visibleIf(fn ($rule) => $rule->where('layout', 'grid'));

    $array = $property->toArray();

    expect($array)->toBe([
        'id' => 'columns',
        'type' => 'test',
        'label' => 'Number of Columns',
        'default' => '3',
        'visibleIf' => [
            'field' => 'layout',
            'operator' => 'equals',
            'value' => 'grid',
        ],
    ]);
});
