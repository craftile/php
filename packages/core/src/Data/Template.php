<?php

namespace Craftile\Core\Data;

/**
 * Fluent builder for creating templates in .craft.php files.
 */
class Template
{
    protected array $blocks = [];

    protected ?array $order = null;

    /**
     * Create a new template.
     */
    public static function make(): static
    {
        return new static; // @phpstan-ignore-line
    }

    /**
     * Add a block to the template.
     *
     * @param  string  $id  Block ID (required)
     * @param  string|class-string<BlockPreset>  $type  Block type or preset class
     * @param  callable|null  $config  Optional callback to configure the block
     */
    public function block(string $id, string $type, ?callable $config = null): static
    {
        // Create new block using factory
        $block = BlockBuilder::forTemplate($id, $type);

        // Apply configuration callback if provided
        if ($config !== null) {
            $config($block);
        }

        // Add block to template
        $this->blocks[] = $block;

        return $this;
    }

    /**
     * Set rendering order for template blocks.
     */
    public function order(array $order): static
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        $data = [];

        if (! empty($this->blocks)) {
            $data['blocks'] = [];
            foreach ($this->blocks as $block) {
                $data['blocks'][$block->id] = $block->toArray();
            }

            if ($this->order !== null) {
                $data['order'] = $this->order;
            }
        }

        return $data;
    }

    /**
     * Return the template data (helper for .craft.php files).
     */
    public function __invoke(): array
    {
        return $this->toArray();
    }
}
