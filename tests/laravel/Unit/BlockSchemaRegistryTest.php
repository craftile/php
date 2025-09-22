<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\BlockSchemaRegistry;
use Craftile\Laravel\Events\BlockSchemaRegistered;
// TestBlock is available from Pest.php
use Illuminate\Support\Facades\Event;

describe('BlockSchemaRegistry', function () {
    beforeEach(function () {
        $this->registry = app(BlockSchemaRegistry::class);
    });

    it('extends core block schema registry', function () {
        expect($this->registry)->toBeInstanceOf(\Craftile\Core\Services\BlockSchemaRegistry::class);
    });

    it('dispatches event when registering schema', function () {
        Event::fake();

        $schema = new BlockSchema(
            slug: 'test',
            class: TestBlock::class,
            name: 'Test Block'
        );

        $this->registry->register($schema);

        Event::assertDispatched(BlockSchemaRegistered::class, function ($event) use ($schema) {
            return $event->schema === $schema;
        });
    });

    it('calls parent register method', function () {
        $schema = new BlockSchema(
            slug: 'test',
            class: TestBlock::class,
            name: 'Test Block'
        );

        $this->registry->register($schema);

        expect($this->registry->hasSchema('test'))->toBeTrue();
        expect($this->registry->getSchema('test'))->toBe($schema);
    });

    it('can group schemas by category', function () {
        $contentSchema = new BlockSchema(
            slug: 'text',
            class: TestBlock::class,
            name: 'Text Block',
            category: 'content'
        );

        $layoutSchema = new BlockSchema(
            slug: 'container',
            class: TestBlock::class,
            name: 'Container Block',
            category: 'layout'
        );

        $defaultSchema = new BlockSchema(
            slug: 'basic',
            class: TestBlock::class,
            name: 'Basic Block'
        );

        $this->registry->register($contentSchema);
        $this->registry->register($layoutSchema);
        $this->registry->register($defaultSchema);

        $grouped = $this->registry->getByCategory();

        expect($grouped)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($grouped->keys()->toArray())->toContain('content', 'layout', 'default');

        expect($grouped->get('content'))->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($grouped->get('content')->count())->toBe(1);
        expect($grouped->get('content')->first())->toBe($contentSchema);

        expect($grouped->get('layout')->count())->toBe(1);
        expect($grouped->get('layout')->first())->toBe($layoutSchema);

        expect($grouped->get('default')->count())->toBe(1);
        expect($grouped->get('default')->first())->toBe($defaultSchema);
    });

    it('handles multiple schemas in same category', function () {
        $textSchema = new BlockSchema(
            slug: 'text',
            class: TestBlock::class,
            name: 'Text Block',
            category: 'content'
        );

        $imageSchema = new BlockSchema(
            slug: 'image',
            class: TestBlock::class,
            name: 'Image Block',
            category: 'content'
        );

        $this->registry->register($textSchema);
        $this->registry->register($imageSchema);

        $grouped = $this->registry->getByCategory();

        expect($grouped->get('content')->count())->toBe(2);
        expect($grouped->get('content')->contains($textSchema))->toBeTrue();
        expect($grouped->get('content')->contains($imageSchema))->toBeTrue();
    });

    it('returns empty collection for category with no schemas', function () {
        $grouped = $this->registry->getByCategory();

        expect($grouped)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($grouped->isEmpty())->toBeTrue();
    });

    it('uses default category for schemas without category', function () {
        $schema = new BlockSchema(
            slug: 'test',
            class: TestBlock::class,
            name: 'Test Block'
            // no category specified
        );

        $this->registry->register($schema);
        $grouped = $this->registry->getByCategory();

        expect($grouped->has('default'))->toBeTrue();
        expect($grouped->get('default')->first())->toBe($schema);
    });

    it('maintains event dispatching when registering multiple schemas', function () {
        Event::fake();

        $schema1 = new BlockSchema('test1', TestBlock::class, 'Test Block 1');
        $schema2 = new BlockSchema('test2', TestBlock::class, 'Test Block 2');

        $this->registry->register($schema1);
        $this->registry->register($schema2);

        Event::assertDispatched(BlockSchemaRegistered::class, 2);
    });
});