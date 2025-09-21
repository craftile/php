<?php

declare(strict_types=1);

namespace Craftile\Core\Tests\Helpers;

use Craftile\Core\Concerns\IsBlock;
use Craftile\Core\Contracts\BlockInterface;

/**
 * Test block implementation for testing purposes.
 */
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
