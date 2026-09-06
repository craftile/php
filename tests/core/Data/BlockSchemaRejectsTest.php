<?php

declare(strict_types=1);

use Craftile\Core\Concerns\IsBlock;
use Craftile\Core\Contracts\BlockInterface;
use Craftile\Core\Data\BlockSchema;

class RejectedTextTestBlock implements BlockInterface
{
    use IsBlock;

    protected static string $type = 'rejected-text';

    protected static string $description = 'Rejected text block';

    protected static string $category = 'content';

    public function render(): string
    {
        return '<p>Text</p>';
    }
}

describe('BlockSchema rejects normalization', function () {
    test('rejects defaults to empty array', function () {
        $schema = new BlockSchema(
            type: 'container',
            slug: 'container',
            class: TestBlock::class,
            name: 'Container',
            accepts: ['*']
        );

        expect($schema->rejects)->toBe([]);
    });

    test('rejects array supports block type strings and patterns', function () {
        $schema = new BlockSchema(
            type: 'container',
            slug: 'container',
            class: TestBlock::class,
            name: 'Container',
            accepts: ['*'],
            rejects: ['text', '@theme/section-*']
        );

        expect($schema->rejects)->toBe(['text', '@theme/section-*']);
    });

    test('rejects array resolves block class names to types', function () {
        $schema = new BlockSchema(
            type: 'container',
            slug: 'container',
            class: TestBlock::class,
            name: 'Container',
            accepts: ['*'],
            rejects: [RejectedTextTestBlock::class, 'image']
        );

        expect($schema->rejects)->toBe(['rejected-text', 'image']);
    });

    test('fromClass reads rejects from block class definition', function () {
        $blockClass = new class implements BlockInterface
        {
            use IsBlock;

            protected static string $type = 'rejecting-container';

            protected static string $description = 'Rejecting container';

            protected static string $category = 'layout';

            protected static array $accepts = ['*'];

            protected static array $rejects = [RejectedTextTestBlock::class];

            public function render(): string
            {
                return '<div></div>';
            }
        };

        $schema = BlockSchema::fromClass($blockClass::class);

        expect($schema->accepts)->toBe(['*']);
        expect($schema->rejects)->toBe(['rejected-text']);
    });

    test('fromClass defaults rejects to empty array when not declared', function () {
        $schema = BlockSchema::fromClass(TestBlock::class);

        expect($schema->rejects)->toBe([]);
    });

    test('toArray includes rejects', function () {
        $schema = new BlockSchema(
            type: 'container',
            slug: 'container',
            class: TestBlock::class,
            name: 'Container',
            accepts: ['*'],
            rejects: ['text']
        );

        expect($schema->toArray()['rejects'])->toBe(['text']);
    });
});
