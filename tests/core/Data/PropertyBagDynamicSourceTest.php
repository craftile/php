<?php

use Craftile\Core\Data\DynamicSource;
use Craftile\Core\Data\PropertyBag;

test('it detects @ prefix and creates DynamicSource object', function () {
    $values = ['title' => '@product.title'];
    $schemas = ['title' => ['type' => 'text']];
    $bag = new PropertyBag($values, $schemas);

    $result = $bag->get('title');

    expect($result)->toBeInstanceOf(DynamicSource::class);
    expect($result->path)->toBe('product.title');
    expect($result->type)->toBe('text');
});

test('it passes context to DynamicSource', function () {
    $values = ['title' => '@product.title'];
    $schemas = ['title' => ['type' => 'text']];
    $bag = new PropertyBag($values, $schemas);

    $context = ['product' => ['title' => 'iPhone']];
    $bag->setContext($context);

    $result = $bag->get('title');

    expect($result)->toBeInstanceOf(DynamicSource::class);
    expect($result->context)->toBe($context);
});

test('it passes default value to DynamicSource', function () {
    $values = ['title' => '@product.title'];
    $schemas = ['title' => ['type' => 'text', 'default' => 'Default Title']];
    $bag = new PropertyBag($values, $schemas);

    $result = $bag->get('title');

    expect($result)->toBeInstanceOf(DynamicSource::class);
    expect($result->default)->toBe('Default Title');
});

test('it handles @ prefix with fallback type', function () {
    $values = ['title' => '@product.title'];
    $schemas = ['title' => []]; // No type specified
    $bag = new PropertyBag($values, $schemas);

    $result = $bag->get('title');

    expect($result)->toBeInstanceOf(DynamicSource::class);
    expect($result->type)->toBe('text'); // Fallback to 'text'
});

test('it does not create DynamicSource for regular values', function () {
    $values = ['title' => 'Static Title'];
    $schemas = ['title' => ['type' => 'text']];
    $bag = new PropertyBag($values, $schemas);

    $result = $bag->get('title');

    expect($result)->toBe('Static Title');
    expect($result)->not->toBeInstanceOf(DynamicSource::class);
});

test('it caches DynamicSource objects', function () {
    $values = ['title' => '@product.title'];
    $schemas = ['title' => ['type' => 'text']];
    $bag = new PropertyBag($values, $schemas);

    $result1 = $bag->get('title');
    $result2 = $bag->get('title');

    expect($result1)->toBe($result2); // Same instance
});

test('it clears cache when context changes', function () {
    $values = ['title' => '@product.title'];
    $schemas = ['title' => ['type' => 'text']];
    $bag = new PropertyBag($values, $schemas);

    $context1 = ['product' => ['title' => 'iPhone 14']];
    $bag->setContext($context1);
    $result1 = $bag->get('title');

    $context2 = ['product' => ['title' => 'iPhone 15']];
    $bag->setContext($context2);
    $result2 = $bag->get('title');

    // Different contexts should produce different DynamicSource instances
    expect($result1->context)->not->toBe($result2->context);
});

test('it handles @ at start of string correctly', function () {
    $values = ['path' => '@user.email'];
    $schemas = ['path' => ['type' => 'text']];
    $bag = new PropertyBag($values, $schemas);

    $result = $bag->get('path');

    expect($result->path)->toBe('user.email'); // @ removed
});

test('it does not treat @ in middle of string as dynamic source', function () {
    $values = ['email' => 'user@example.com'];
    $schemas = ['email' => ['type' => 'text']];
    $bag = new PropertyBag($values, $schemas);

    $result = $bag->get('email');

    expect($result)->toBe('user@example.com');
    expect($result)->not->toBeInstanceOf(DynamicSource::class);
});
