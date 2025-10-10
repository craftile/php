<?php

declare(strict_types=1);

use Craftile\Core\Concerns\IsBlock;
use Craftile\Core\Contracts\BlockInterface;
use Craftile\Core\Data\BlockSchema;

// Additional test blocks for accepts testing
class TextTestBlock implements BlockInterface
{
    use IsBlock;

    protected static string $type = 'text';

    protected static string $description = 'Text block';

    protected static string $category = 'content';

    public function render(): string
    {
        return '<p>Text</p>';
    }
}

class ImageTestBlock implements BlockInterface
{
    use IsBlock;

    protected static string $type = 'image';

    protected static string $description = 'Image block';

    protected static string $category = 'media';

    public function render(): string
    {
        return '<img/>';
    }
}

class ButtonTestBlock implements BlockInterface
{
    use IsBlock;

    protected static string $type = 'button';

    protected static string $description = 'Button block';

    protected static string $category = 'interactive';

    public function render(): string
    {
        return '<button>Click me</button>';
    }
}

describe('BlockSchema accepts normalization', function () {
    test('accepts array supports block type strings', function () {
        $schema = new BlockSchema(
            type: 'container',
            slug: 'container',
            class: TestBlock::class,
            name: 'Container',
            accepts: ['text', 'image']
        );

        expect($schema->accepts)->toBe(['text', 'image']);
    });

    test('accepts array resolves block class names to types', function () {
        $schema = new BlockSchema(
            type: 'section',
            slug: 'section',
            class: TestBlock::class,
            name: 'Section',
            accepts: [TextTestBlock::class, ImageTestBlock::class]
        );

        expect($schema->accepts)->toBe(['text', 'image']);
    });

    test('accepts array supports mixed class names and type strings', function () {
        $schema = new BlockSchema(
            type: 'section',
            slug: 'section',
            class: TestBlock::class,
            name: 'Section',
            accepts: ['hero', TextTestBlock::class, '*']
        );

        expect($schema->accepts)->toBe(['hero', 'text', '*']);
    });

    test('accepts array passes through wildcard unchanged', function () {
        $schema = new BlockSchema(
            type: 'container',
            slug: 'container',
            class: TestBlock::class,
            name: 'Container',
            accepts: ['*']
        );

        expect($schema->accepts)->toBe(['*']);
    });

    test('accepts array handles empty array', function () {
        $schema = new BlockSchema(
            type: 'leaf',
            slug: 'leaf',
            class: TestBlock::class,
            name: 'Leaf Block',
            accepts: []
        );

        expect($schema->accepts)->toBe([]);
    });

    test('accepts array ignores non-block classes', function () {
        $schema = new BlockSchema(
            type: 'test',
            slug: 'test',
            class: TestBlock::class,
            name: 'Test',
            accepts: [stdClass::class, 'text']
        );

        // stdClass is not a BlockInterface, so it's kept as-is
        expect($schema->accepts)->toBe([stdClass::class, 'text']);
    });

    test('accepts array handles non-existent classes gracefully', function () {
        $schema = new BlockSchema(
            type: 'test',
            slug: 'test',
            class: TestBlock::class,
            name: 'Test',
            accepts: ['NonExistentClass', 'text']
        );

        // Non-existent class is kept as-is (might be a type string)
        expect($schema->accepts)->toBe(['NonExistentClass', 'text']);
    });

    test('fromClass normalizes accepts from block class definition', function () {
        // Create a block with class-based accepts
        $blockClass = new class implements BlockInterface
        {
            use IsBlock;

            protected static string $type = 'custom-container';

            protected static string $description = 'Custom container';

            protected static string $category = 'layout';

            protected static array $accepts = [
                TextTestBlock::class,
                ButtonTestBlock::class,
            ];

            public function render(): string
            {
                return '<div></div>';
            }
        };

        $schema = BlockSchema::fromClass($blockClass::class);

        expect($schema->accepts)->toBe(['text', 'button']);
        expect($schema->accepts)->not->toContain(TextTestBlock::class);
        expect($schema->accepts)->not->toContain(ButtonTestBlock::class);
    });

    test('multiple instances of same block class resolve to same type', function () {
        $schema = new BlockSchema(
            type: 'multi',
            slug: 'multi',
            class: TestBlock::class,
            name: 'Multi',
            accepts: [TextTestBlock::class, TextTestBlock::class, 'text']
        );

        // All resolve to 'text', array_map doesn't deduplicate
        expect($schema->accepts)->toBe(['text', 'text', 'text']);
    });

    test('preserves order of accepts array', function () {
        $schema = new BlockSchema(
            type: 'ordered',
            slug: 'ordered',
            class: TestBlock::class,
            name: 'Ordered',
            accepts: [ButtonTestBlock::class, 'hero', ImageTestBlock::class, 'text']
        );

        expect($schema->accepts)->toBe(['button', 'hero', 'image', 'text']);
    });

    test('works with complex mixed scenarios', function () {
        $schema = new BlockSchema(
            type: 'complex',
            slug: 'complex',
            class: TestBlock::class,
            name: 'Complex',
            accepts: [
                '*',                      // Wildcard
                'custom-type',            // String type
                TextTestBlock::class,     // Valid block class
                stdClass::class,          // Non-block class
                'AnotherType',            // Another string
                ImageTestBlock::class,    // Another valid block class
            ]
        );

        expect($schema->accepts)->toBe([
            '*',
            'custom-type',
            'text',           // Resolved from TextTestBlock
            stdClass::class,  // Kept as-is
            'AnotherType',
            'image',          // Resolved from ImageTestBlock
        ]);
    });
});
