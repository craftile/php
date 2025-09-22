<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockSchema;

describe('BlockSchema', function () {
    it('can be created with all parameters', function () {
        $schema = new BlockSchema(
            slug: 'text',
            class: TestBlock::class,
            name: 'Text Block',
            description: 'A simple text block',
            icon: 'text-icon',
            category: 'content'
        );

        expect($schema->type)->toBe('text');
        expect($schema->slug)->toBe('text');
        expect($schema->class)->toBe(TestBlock::class);
        expect($schema->name)->toBe('Text Block');
        expect($schema->description)->toBe('A simple text block');
        expect($schema->icon)->toBe('text-icon');
        expect($schema->category)->toBe('content');
    });

    it('has sensible defaults for optional parameters', function () {
        $schema = new BlockSchema('text', TestBlock::class, 'Text Block');

        expect($schema->description)->toBe(null);
        expect($schema->icon)->toBe(null);
        expect($schema->category)->toBe(null);
    });

    it('can be created from block class', function () {
        $schema = BlockSchema::fromClass(TestBlock::class);

        expect($schema->type)->toBe('test-block'); // From IsBlock trait
        expect($schema->class)->toBe(TestBlock::class);
        expect($schema->name)->toBe('Test Block'); // From IsBlock trait
        expect($schema->description)->toBe('A test block for testing');
        expect($schema->icon)->toBe('test-icon');
        expect($schema->category)->toBe('test');
    });

    it('throws exception when creating from non-existent class', function () {
        expect(fn() => BlockSchema::fromClass('NonExistentClass'))
            ->toThrow(InvalidArgumentException::class, 'Block class NonExistentClass does not exist');
    });

    it('throws exception when creating from non-block class', function () {
        expect(fn() => BlockSchema::fromClass(stdClass::class))
            ->toThrow(InvalidArgumentException::class, 'must implement BlockInterface');
    });

    it('can manage properties via constructor', function () {
        $properties = [
            ['id' => 'content', 'type' => 'text', 'default' => ''],
            ['id' => 'title', 'type' => 'text', 'default' => 'Title'],
        ];

        $schema = new BlockSchema(
            'test',
            TestBlock::class,
            'Test Block',
            properties: $properties
        );

        expect($schema->properties)->toBe($properties);
    });

    it('can work with object properties', function () {
        $property = new class
        {
            public function getId(): string
            {
                return 'test';
            }

            public function toArray(): array
            {
                return ['id' => 'test', 'type' => 'text'];
            }
        };

        $schema = new BlockSchema(
            'test',
            TestBlock::class,
            'Test Block',
            properties: [$property]
        );

        expect($schema->properties)->toBe([$property]);
    });

    it('can manage accepted child types via constructor', function () {
        $schema = new BlockSchema(
            'test',
            TestBlock::class,
            'Test Block',
            accepts: ['text', 'image']
        );

        expect($schema->accepts)->toBe(['text', 'image']);
    });

    it('provides properties via constructor', function () {
        $properties = [
            ['id' => 'title', 'type' => 'text', 'default' => 'Default Title'],
            ['id' => 'size', 'type' => 'select', 'default' => 'medium'],
        ];

        $schema = new BlockSchema(
            'test',
            TestBlock::class,
            'Test Block',
            properties: $properties
        );

        expect($schema->properties)->toBe($properties);
    });

    it('works with object properties', function () {
        $property = new class
        {
            public function getId(): string
            {
                return 'test';
            }

            public function isOptional(): bool
            {
                return true;
            }

            public function toArray(): array
            {
                return ['id' => 'test', 'type' => 'text', 'default' => 'default value'];
            }
        };

        $schema = new BlockSchema(
            'test',
            TestBlock::class,
            'Test Block',
            properties: [$property]
        );

        expect($schema->properties)->toBe([$property]);
    });

    it('can be converted to array', function () {
        $schema = new BlockSchema(
            'text',
            TestBlock::class,
            'Text Block',
            'A simple text block',
            'text-icon',
            'content',
            [['id' => 'content', 'type' => 'text']],
            ['text', 'image']
        );

        $array = $schema->toArray();

        expect($array)->toHaveKey('type', 'text');
        expect($array)->toHaveKey('class', TestBlock::class);
        expect($array)->toHaveKey('name', 'Text Block');
        expect($array)->toHaveKey('description', 'A simple text block');
        expect($array)->toHaveKey('icon', 'text-icon');
        expect($array)->toHaveKey('category', 'content');
        expect($array)->toHaveKey('properties');
        expect($array)->toHaveKey('accepts', ['text', 'image']);
    });

    it('throws exception when validating with empty type', function () {
        $schema = new BlockSchema('', TestBlock::class, 'Test');

        expect(fn() => $schema->validate())
            ->toThrow(InvalidArgumentException::class, 'Block type cannot be empty');
    });

    it('throws exception when validating with non-existent class', function () {
        $schema = new BlockSchema('test', 'NonExistentClass', 'Test');

        expect(fn() => $schema->validate())
            ->toThrow(InvalidArgumentException::class, 'Block class NonExistentClass does not exist');
    });

    it('throws exception when validating with empty label', function () {
        $schema = new BlockSchema('test', TestBlock::class, '');

        expect(fn() => $schema->validate())
            ->toThrow(InvalidArgumentException::class, 'Block label cannot be empty');
    });

    it('is json serializable', function () {
        $schema = sampleBlockSchema();

        expect($schema->jsonSerialize())->toBe($schema->toArray());

        $json = json_encode($schema);
        expect($json)->toBeString();

        $decoded = json_decode($json, true);
        expect($decoded)->toHaveKey('type', 'text');
    });
});
