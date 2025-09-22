<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockSchema;
use Craftile\Core\Services\BlockSchemaRegistry;

describe('BlockSchemaRegistry', function () {
    beforeEach(function () {
        $this->registry = new BlockSchemaRegistry();
    });

    it('can register a schema', function () {
        $schema = new BlockSchema(
            slug: 'text',
            class: TestBlock::class,
            name: 'Text Block'
        );

        $this->registry->register($schema);

        expect($this->registry->hasSchema('text'))->toBeTrue();
        expect($this->registry->getSchema('text'))->toBe($schema);
    });

    it('can register multiple schemas', function () {
        $textSchema = new BlockSchema('text', TestBlock::class, 'Text Block');
        $imageSchema = new BlockSchema('image', TestBlock::class, 'Image Block');

        $this->registry->register($textSchema);
        $this->registry->register($imageSchema);

        expect($this->registry->hasSchema('text'))->toBeTrue();
        expect($this->registry->hasSchema('image'))->toBeTrue();
        expect($this->registry->getSchema('text'))->toBe($textSchema);
        expect($this->registry->getSchema('image'))->toBe($imageSchema);
    });

    it('overwrites existing schema with same type', function () {
        $firstSchema = new BlockSchema('text', TestBlock::class, 'First Text');
        $secondSchema = new BlockSchema('text', TestBlock::class, 'Second Text');

        $this->registry->register($firstSchema);
        expect($this->registry->getSchema('text')->name)->toBe('First Text');

        $this->registry->register($secondSchema);
        expect($this->registry->getSchema('text')->name)->toBe('Second Text');
    });

    it('returns null for non-existent schema', function () {
        expect($this->registry->getSchema('nonexistent'))->toBeNull();
    });

    it('returns false when checking for non-existent schema', function () {
        expect($this->registry->hasSchema('nonexistent'))->toBeFalse();
    });

    it('can get all registered schemas', function () {
        $textSchema = new BlockSchema('text', TestBlock::class, 'Text Block');
        $imageSchema = new BlockSchema('image', TestBlock::class, 'Image Block');

        $this->registry->register($textSchema);
        $this->registry->register($imageSchema);

        $allSchemas = $this->registry->getAllSchemas();

        expect($allSchemas)->toHaveCount(2);
        expect($allSchemas)->toHaveKey('text', $textSchema);
        expect($allSchemas)->toHaveKey('image', $imageSchema);
    });

    it('returns empty array when no schemas registered', function () {
        expect($this->registry->getAllSchemas())->toBe([]);
    });

    it('can get schemas using all alias', function () {
        $schema = new BlockSchema('test', TestBlock::class, 'Test Block');
        $this->registry->register($schema);

        expect($this->registry->all())->toBe($this->registry->getAllSchemas());
    });

    it('can clear all schemas', function () {
        $schema = new BlockSchema('text', TestBlock::class, 'Text Block');
        $this->registry->register($schema);

        expect($this->registry->getAllSchemas())->toHaveCount(1);

        $this->registry->clear();

        expect($this->registry->getAllSchemas())->toBe([]);
        expect($this->registry->hasSchema('text'))->toBeFalse();
    });

    it('can get registered types', function () {
        expect($this->registry->getRegisteredTypes())->toBe([]);

        $this->registry->register(new BlockSchema('text', TestBlock::class, 'Text'));
        expect($this->registry->getRegisteredTypes())->toBe(['text']);

        $this->registry->register(new BlockSchema('image', TestBlock::class, 'Image'));
        expect($this->registry->getRegisteredTypes())->toContain('text');
        expect($this->registry->getRegisteredTypes())->toContain('image');
        expect($this->registry->getRegisteredTypes())->toHaveCount(2);
    });

    it('preserves schema type and slug relationship', function () {
        $schema = new BlockSchema(
            slug: 'custom-block',
            class: TestBlock::class,
            name: 'Custom Block'
        );

        $this->registry->register($schema);

        // Should be retrievable by type (which equals slug)
        expect($this->registry->getSchema('custom-block'))->toBe($schema);
        expect($this->registry->hasSchema('custom-block'))->toBeTrue();
    });

    it('can register schemas with different properties', function () {
        $textSchema = new BlockSchema(
            slug: 'text',
            class: TestBlock::class,
            name: 'Text Block',
            description: 'A text block',
            icon: 'text-icon',
            category: 'content',
            properties: [['key' => 'content', 'type' => 'text']],
            accepts: ['inline']
        );

        $this->registry->register($textSchema);

        $retrieved = $this->registry->getSchema('text');
        expect($retrieved->description)->toBe('A text block');
        expect($retrieved->icon)->toBe('text-icon');
        expect($retrieved->category)->toBe('content');
        expect($retrieved->properties)->toHaveCount(1);
        expect($retrieved->accepts)->toBe(['inline']);
    });

    it('handles registration of schema created from class', function () {
        $schema = BlockSchema::fromClass(TestBlock::class);
        $this->registry->register($schema);

        expect($this->registry->hasSchema($schema->type))->toBeTrue();
        expect($this->registry->getSchema($schema->type))->toBe($schema);
    });

    it('can remove schemas', function () {
        $schema = new BlockSchema('text', TestBlock::class, 'Text Block');
        $this->registry->register($schema);

        expect($this->registry->hasSchema('text'))->toBeTrue();

        $this->registry->removeSchema('text');

        expect($this->registry->hasSchema('text'))->toBeFalse();
        expect($this->registry->getSchema('text'))->toBeNull();
    });

    it('can use get alias for getSchema', function () {
        $schema = new BlockSchema('text', TestBlock::class, 'Text Block');
        $this->registry->register($schema);

        expect($this->registry->get('text'))->toBe($schema);
        expect($this->registry->get('text'))->toBe($this->registry->getSchema('text'));
    });
});