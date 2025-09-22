<?php

declare(strict_types=1);

use Craftile\Core\Concerns\IsBlock;
use Craftile\Core\Contracts\BlockInterface;
use Craftile\Core\Data\BlockSchema;
use Tests\Core\TestCase as CoreTestCase;
use Tests\Laravel\TestCase as LaravelTestCase;

uses(CoreTestCase::class)->in('core');
uses(LaravelTestCase::class)->in('laravel/Unit', 'laravel/Feature');

// Test Block for all tests
class TestBlock implements BlockInterface
{
    use IsBlock;

    protected static string $description = 'A test block for testing';

    protected static array $properties = [
        ['key' => 'content', 'type' => 'text', 'default' => ''],
        ['key' => 'size', 'type' => 'select', 'options' => ['small', 'large']],
    ];

    protected static array $accepts = ['*'];

    protected static string $icon = 'test-icon';

    protected static string $category = 'test';

    public function render(): string
    {
        return '<div>Test block content</div>';
    }
}

// Helper functions
function sampleBlockData(): array
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

function sampleBlockSchema(): BlockSchema
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
