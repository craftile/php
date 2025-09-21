<?php

declare(strict_types=1);

namespace Craftile\Core\Tests\Helpers;

use Craftile\Core\Data\BlockSchema;

/**
 * Test helper functions.
 */
class TestHelpers
{
    public static function sampleBlockData(): array
    {
        return [
            'id' => 'test-block-1',
            'type' => 'text',
            'properties' => [
                'content' => 'Hello World',
                'size' => 'large',
                'color' => 'blue',
            ],
            'parentId' => null,
            'children' => ['child-1', 'child-2'],
            'disabled' => false,
            'static' => false,
            'repeated' => false,
            'semanticId' => 'hero-text',
        ];
    }

    public static function sampleBlockSchema(): BlockSchema
    {
        return new BlockSchema(
            slug: 'text',
            class: TestBlock::class,
            name: 'Text Block',
            description: 'A simple text block',
            icon: 'text-icon',
            category: 'content'
        );
    }
}
