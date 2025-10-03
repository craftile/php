<?php

declare(strict_types=1);

namespace Tests\Laravel\Stubs\Discovery;

use Craftile\Core\Concerns\IsBlock;
use Craftile\Core\Contracts\BlockInterface;
use Craftile\Core\Data\BlockPreset;
use Craftile\Core\Data\PresetChild;

class ContainerBlock implements BlockInterface
{
    use IsBlock;

    protected static string $type = 'container';

    protected static string $description = 'A container block that can hold other blocks';

    protected static string $category = 'layout';

    protected static string $icon = '<svg>...</svg>';

    protected static array $accepts = ['*'];

    protected static array $properties = [
        ['id' => 'gap', 'type' => 'number', 'default' => 16],
        ['id' => 'padding', 'type' => 'number', 'default' => 0],
        ['id' => 'backgroundColor', 'type' => 'color', 'default' => null],
    ];

    /**
     * Define block presets using fluent API.
     */
    protected static array $presets = [
        // Fluent API example
        // BlockPreset::make('Heading and Text')
        //     ->description('Container with a heading and description')
        //     ->properties(['gap' => 12])
        //     ->blocks([
        //         PresetChild::make('text')->id('heading')->properties(['content' => '<h2>Title</h2>']),
        //         PresetChild::make('text')->id('description')->properties(['content' => '<p>Description</p>']),
        //     ]),

        // Array syntax example
        [
            'name' => 'Hero Section',
            'description' => 'A hero section with heading, text, and call-to-action button',
            'icon' => '<svg>...</svg>',
            'properties' => [
                'gap' => 24,
                'padding' => 40,
                'backgroundColor' => '#f5f5f5',
            ],
            'children' => [
                [
                    'type' => 'text',
                    'id' => 'heading',
                    'static' => true,
                    'properties' => [
                        'content' => '<h1>Welcome to Our Platform</h1>',
                    ],
                ],
                [
                    'type' => 'text',
                    'id' => 'description',
                    'properties' => [
                        'content' => '<p>Get started with our powerful tools today.</p>',
                    ],
                ],
                [
                    'type' => 'button',
                    'id' => 'cta',
                    'properties' => [
                        'label' => 'Get Started',
                        'variant' => 'primary',
                    ],
                ],
            ],
        ],
    ];

    public function render(): string
    {
        return '<div class="container">Container Block</div>';
    }
}
