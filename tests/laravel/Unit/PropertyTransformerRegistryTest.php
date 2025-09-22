<?php

declare(strict_types=1);

use Craftile\Laravel\Contracts\PropertyTransformerInterface;
use Craftile\Laravel\PropertyTransformerRegistry;

describe('PropertyTransformerRegistry', function () {
    beforeEach(function () {
        $this->registry = new PropertyTransformerRegistry;
    });

    it('can register and use a callable transformer', function () {
        $this->registry->register('uppercase', fn ($value) => strtoupper($value));

        $result = $this->registry->transform('hello', 'uppercase');

        expect($result)->toBe('HELLO');
    });

    it('can register and use an object transformer', function () {
        $transformer = new class implements PropertyTransformerInterface
        {
            public function transform(mixed $value): mixed
            {
                return json_decode($value, true);
            }
        };

        $this->registry->register('json', $transformer);

        $result = $this->registry->transform('{"name":"test"}', 'json');

        expect($result)->toBe(['name' => 'test']);
    });

    it('returns original value for unknown types', function () {
        $result = $this->registry->transform('test', 'unknown');

        expect($result)->toBe('test');
    });

    it('can check if transformer exists', function () {
        $this->registry->register('test', fn ($value) => $value);

        expect($this->registry->has('test'))->toBeTrue();
        expect($this->registry->has('unknown'))->toBeFalse();
    });

    it('can get registered types', function () {
        $this->registry->register('type1', fn ($value) => $value);
        $this->registry->register('type2', fn ($value) => $value);

        $types = $this->registry->getRegisteredTypes();

        expect($types)->toContain('type1');
        expect($types)->toContain('type2');
        expect($types)->toHaveCount(2);
    });

    it('can remove transformers', function () {
        $this->registry->register('test', fn ($value) => $value);

        expect($this->registry->has('test'))->toBeTrue();

        $this->registry->remove('test');

        expect($this->registry->has('test'))->toBeFalse();
    });

    it('can clear all transformers', function () {
        $this->registry->register('type1', fn ($value) => $value);
        $this->registry->register('type2', fn ($value) => $value);

        expect($this->registry->getRegisteredTypes())->toHaveCount(2);

        $this->registry->clear();

        expect($this->registry->getRegisteredTypes())->toHaveCount(0);
    });
});
