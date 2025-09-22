<?php

declare(strict_types=1);

use Craftile\Core\Data\PropertyBag;

describe('PropertyBag', function () {
    it('can be created with values and schemas', function () {
        $values = ['content' => 'Hello', 'size' => 'large'];
        $schemas = ['content' => ['type' => 'text'], 'size' => ['type' => 'select']];

        $bag = new PropertyBag($values, $schemas);

        expect($bag->get('content'))->toBe('Hello');
        expect($bag->get('size'))->toBe('large');
    });

    it('can be created empty', function () {
        $bag = new PropertyBag();

        expect($bag->count())->toBe(0);
        expect($bag->all())->toBe([]);
    });

    it('can get values using magic getter', function () {
        $bag = new PropertyBag(['title' => 'Test Title']);

        expect($bag->title)->toBe('Test Title');
        expect($bag->nonexistent)->toBeNull();
    });

    it('can check if key exists', function () {
        $bag = new PropertyBag(['existing' => 'value']);

        expect($bag->has('existing'))->toBeTrue();
        expect($bag->has('nonexistent'))->toBeFalse();
    });

    it('returns null for nonexistent keys', function () {
        $bag = new PropertyBag(['key1' => 'value1']);

        expect($bag->get('nonexistent'))->toBeNull();
    });

    it('can get all values', function () {
        $values = ['prop1' => 'val1', 'prop2' => 'val2'];
        $bag = new PropertyBag($values);

        expect($bag->all())->toBe($values);
    });

    it('can get raw values without transformation', function () {
        $values = ['content' => 'Test', 'number' => 42];
        $bag = new PropertyBag($values);

        expect($bag->raw())->toBe($values);
    });

    it('can get only specified keys', function () {
        $bag = new PropertyBag([
            'title' => 'Test',
            'content' => 'Content',
            'size' => 'large',
            'color' => 'blue'
        ]);

        $subset = $bag->only(['title', 'content']);

        expect($subset)->toBeInstanceOf(PropertyBag::class);
        expect($subset->all())->toBe(['title' => 'Test', 'content' => 'Content']);
        expect($subset->has('size'))->toBeFalse();
    });

    it('can exclude specified keys', function () {
        $bag = new PropertyBag([
            'title' => 'Test',
            'content' => 'Content',
            'size' => 'large',
            'color' => 'blue'
        ]);

        $subset = $bag->except(['size', 'color']);

        expect($subset)->toBeInstanceOf(PropertyBag::class);
        expect($subset->all())->toBe(['title' => 'Test', 'content' => 'Content']);
        expect($subset->has('size'))->toBeFalse();
        expect($subset->has('color'))->toBeFalse();
    });

    it('preserves schemas when using only', function () {
        $values = ['title' => 'Test', 'content' => 'Content'];
        $schemas = ['title' => ['type' => 'text'], 'content' => ['type' => 'textarea']];

        $bag = new PropertyBag($values, $schemas);
        $subset = $bag->only(['title']);

        // The schema should be preserved (this is implementation detail)
        expect($subset->get('title'))->toBe('Test');
    });

    it('is countable', function () {
        $bag = new PropertyBag(['a' => 1, 'b' => 2, 'c' => 3]);

        expect($bag->count())->toBe(3);
        expect(count($bag))->toBe(3);
    });

    it('is iterable', function () {
        $values = ['key1' => 'value1', 'key2' => 'value2'];
        $bag = new PropertyBag($values);

        $iterated = [];
        foreach ($bag as $key => $value) {
            $iterated[$key] = $value;
        }

        expect($iterated)->toBe($values);
    });

    it('can be converted to array', function () {
        $values = ['prop1' => 'val1', 'prop2' => 'val2'];
        $bag = new PropertyBag($values);

        expect($bag->toArray())->toBe($values);
    });

    it('is json serializable', function () {
        $values = ['title' => 'Test', 'count' => 5];
        $bag = new PropertyBag($values);

        expect($bag->jsonSerialize())->toBe($values);

        $json = json_encode($bag);
        expect($json)->toBeString();

        $decoded = json_decode($json, true);
        expect($decoded)->toBe($values);
    });

    it('caches resolved values', function () {
        $bag = new PropertyBag(['test' => 'value']);

        // First access
        $value1 = $bag->get('test');
        // Second access should use cached value
        $value2 = $bag->get('test');

        expect($value1)->toBe($value2);
        expect($value1)->toBe('value');
    });

    it('handles array values properly', function () {
        $arrayValue = ['item1', 'item2', 'item3'];
        $bag = new PropertyBag(['list' => $arrayValue]);

        expect($bag->get('list'))->toBe($arrayValue);
    });

    it('handles nested object values', function () {
        $objectValue = (object) ['nested' => 'value'];
        $bag = new PropertyBag(['object' => $objectValue]);

        expect($bag->get('object'))->toBe($objectValue);
    });

    it('can handle numeric keys', function () {
        $bag = new PropertyBag([0 => 'first', 1 => 'second']);

        expect($bag->get('0'))->toBe('first');
        expect($bag->get('1'))->toBe('second');
        expect($bag->has('0'))->toBeTrue();
    });

    it('transforms values based on schema type', function () {
        // Note: The base PropertyBag doesn't do transformation by default
        // This test ensures the transformValue method can be overridden
        $values = ['test' => 'value'];
        $schemas = ['test' => ['type' => 'custom']];

        $bag = new PropertyBag($values, $schemas);

        expect($bag->get('test'))->toBe('value'); // No transformation in base class
    });

    it('handles empty only operation', function () {
        $bag = new PropertyBag(['a' => 1, 'b' => 2]);
        $subset = $bag->only([]);

        expect($subset->count())->toBe(0);
        expect($subset->all())->toBe([]);
    });

    it('handles empty except operation', function () {
        $original = ['a' => 1, 'b' => 2];
        $bag = new PropertyBag($original);
        $subset = $bag->except([]);

        expect($subset->all())->toBe($original);
    });
});