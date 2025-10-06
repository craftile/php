<?php

use Craftile\Core\Data\DynamicSource;
use Craftile\Laravel\PropertyTransformers\DynamicSourceTransformer;

beforeEach(function () {
    $this->transformer = new DynamicSourceTransformer;
});

test('it resolves simple path', function () {
    $context = ['product' => ['title' => 'iPhone 15']];
    $source = new DynamicSource('product.title', 'text', $context);

    $result = $this->transformer->transform($source);

    expect($result)->toBe('iPhone 15');
});

test('it resolves nested path', function () {
    $context = ['product' => ['variants' => ['first' => ['price' => 999]]]];
    $source = new DynamicSource('product.variants.first.price', 'number', $context);

    $result = $this->transformer->transform($source);

    expect($result)->toBe(999);
});

test('it returns default when path not found', function () {
    $context = ['product' => ['title' => 'iPhone']];
    $source = new DynamicSource('product.missing', 'text', $context, 'Default Value');

    $result = $this->transformer->transform($source);

    expect($result)->toBe('Default Value');
});

test('it returns null when no default and path not found', function () {
    $context = ['product' => ['title' => 'iPhone']];
    $source = new DynamicSource('missing.path', 'text', $context);

    $result = $this->transformer->transform($source);

    expect($result)->toBeNull();
});

test('it returns value unchanged if not dynamic source', function () {
    $result = $this->transformer->transform('regular string');

    expect($result)->toBe('regular string');
});

test('it handles array context', function () {
    $context = [
        'user' => ['email' => 'user@example.com'],
        'cart' => ['total' => 150],
    ];
    $source = new DynamicSource('cart.total', 'number', $context);

    $result = $this->transformer->transform($source);

    expect($result)->toBe(150);
});
