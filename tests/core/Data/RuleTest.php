<?php

use Craftile\Core\Data\Rule;

test('it creates a simple equals condition', function () {
    $rule = Rule::make()->when('layout', 'grid');

    expect($rule->toArray())->toBe([
        'field' => 'layout',
        'operator' => 'equals',
        'value' => 'grid',
    ]);
});

test('it creates equals condition with explicit operator', function () {
    $rule = Rule::make()->when('status', '=', 'published');

    expect($rule->toArray())->toBe([
        'field' => 'status',
        'operator' => 'equals',
        'value' => 'published',
    ]);
});

test('it creates not equals condition', function () {
    $rule = Rule::make()->whenNot('status', 'draft');

    expect($rule->toArray())->toBe([
        'field' => 'status',
        'operator' => 'not_equals',
        'value' => 'draft',
    ]);
});

test('it creates in condition', function () {
    $rule = Rule::make()->whenIn('type', ['image', 'video']);

    expect($rule->toArray())->toBe([
        'field' => 'type',
        'operator' => 'in',
        'value' => ['image', 'video'],
    ]);
});

test('it creates not in condition', function () {
    $rule = Rule::make()->whenNotIn('type', ['draft', 'archived']);

    expect($rule->toArray())->toBe([
        'field' => 'type',
        'operator' => 'not_in',
        'value' => ['draft', 'archived'],
    ]);
});

test('it creates greater than condition', function () {
    $rule = Rule::make()->whenGt('views', 1000);

    expect($rule->toArray())->toBe([
        'field' => 'views',
        'operator' => 'greater_than',
        'value' => 1000,
    ]);
});

test('it creates less than condition', function () {
    $rule = Rule::make()->whenLt('views', 10);

    expect($rule->toArray())->toBe([
        'field' => 'views',
        'operator' => 'less_than',
        'value' => 10,
    ]);
});

test('it creates truthy condition', function () {
    $rule = Rule::make()->whenTruthy('is_active');

    expect($rule->toArray())->toBe([
        'field' => 'is_active',
        'operator' => 'truthy',
    ]);
});

test('it creates falsy condition', function () {
    $rule = Rule::make()->whenFalsy('is_hidden');

    expect($rule->toArray())->toBe([
        'field' => 'is_hidden',
        'operator' => 'falsy',
    ]);
});

test('it creates AND group with multiple conditions', function () {
    $rule = Rule::make()
        ->when('layout', 'grid')
        ->when('status', 'published');

    expect($rule->toArray())->toBe([
        'and' => [
            [
                'field' => 'layout',
                'operator' => 'equals',
                'value' => 'grid',
            ],
            [
                'field' => 'status',
                'operator' => 'equals',
                'value' => 'published',
            ],
        ],
    ]);
});

test('it creates nested AND group', function () {
    $rule = Rule::make()
        ->when('layout', 'grid')
        ->and(fn ($r) => $r->when('status', 'published')->when('visible', true));

    expect($rule->toArray())->toBe([
        'and' => [
            [
                'field' => 'layout',
                'operator' => 'equals',
                'value' => 'grid',
            ],
            [
                'and' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => 'published',
                    ],
                    [
                        'field' => 'visible',
                        'operator' => 'equals',
                        'value' => true,
                    ],
                ],
            ],
        ],
    ]);
});

test('it creates OR group', function () {
    $rule = Rule::make()
        ->when('layout', 'flex')
        ->or(fn ($r) => $r->when('type', 'image')->when('status', 'published'));

    $result = $rule->toArray();

    expect($result)->toHaveKey('or');
    expect($result['or'])->toHaveCount(2);
});

test('it normalizes operator symbols', function () {
    $rule1 = Rule::make()->when('field', '==', 'value');
    $rule2 = Rule::make()->when('field', '!=', 'value');
    $rule3 = Rule::make()->when('field', '>', 100);
    $rule4 = Rule::make()->when('field', '<', 100);

    expect($rule1->toArray()['operator'])->toBe('equals');
    expect($rule2->toArray()['operator'])->toBe('not_equals');
    expect($rule3->toArray()['operator'])->toBe('greater_than');
    expect($rule4->toArray()['operator'])->toBe('less_than');
});

test('it handles complex nested logic', function () {
    $rule = Rule::make()
        ->when('layout', 'grid')
        ->or(fn ($r) => $r->when('type', 'image')
            ->when('status', 'published')
        );

    $result = $rule->toArray();

    expect($result)->toHaveKey('or');
    expect($result['or'])->toBeArray();
});

test('it supports fluent chaining', function () {
    $rule = Rule::make()
        ->when('layout', 'grid')
        ->whenIn('status', ['published', 'draft'])
        ->whenGt('views', 100);

    expect($rule->toArray())->toHaveKey('and');
    expect($rule->toArray()['and'])->toHaveCount(3);
});
