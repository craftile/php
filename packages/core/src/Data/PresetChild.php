<?php

namespace Craftile\Core\Data;

use JsonSerializable;

/**
 * Represents a child block within a preset configuration.
 * Supports fluent API for building nested block structures.
 *
 * @phpstan-consistent-constructor
 */
class PresetChild implements JsonSerializable
{
    public string $type;

    public ?string $id = null;

    public ?string $name = null;

    public array $properties = [];

    public bool $static = false;

    public bool $ghost = false;

    public bool $repeated = false;

    public array $children = [];

    public ?array $childrenOrder = null;

    /**
     * Create a new preset child instance.
     */
    public function __construct(?string $type = null)
    {
        // Call build() first to allow subclass configuration
        $this->build();

        // Set type with priority: constructor param > build() set value > getType()
        $this->type = $type ?? $this->type ?? $this->getType();
    }

    /**
     * Create a new preset child.
     */
    public static function make(?string $type = null): static
    {
        return new static($type);
    }

    /**
     * Get default preset child type.
     * Can be overridden in subclasses to provide a custom type.
     */
    protected function getType(): string
    {
        $class = (new \ReflectionClass(static::class))->getShortName();

        // Remove "PresetChild" or "Child" suffix if present
        $class = preg_replace('/(PresetChild|Child)$/', '', $class);

        // Convert PascalCase to kebab-case (e.g., "Paragraph" -> "paragraph")
        $kebab = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $class));

        return $kebab;
    }

    /**
     * Build the preset child configuration.
     * Override this method in subclasses to define structure.
     */
    protected function build(): void
    {
        // Base implementation does nothing
    }

    /**
     * Set the block type.
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the block's semantic ID.
     */
    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the block's custom display name.
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the block's properties.
     */
    public function properties(array $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Mark the block as static (non-editable).
     */
    public function static(bool $static = true): static
    {
        $this->static = $static;

        return $this;
    }

    /**
     * Mark the block as a ghost block (data-only, not rendered).
     */
    public function ghost(bool $ghost = true): static
    {
        $this->ghost = $ghost;

        return $this;
    }

    /**
     * Mark the block as repeated (rendered in a loop).
     */
    public function repeated(bool $repeated = true): static
    {
        $this->repeated = $repeated;

        return $this;
    }

    /**
     * Set rendering order for children blocks.
     */
    public function order(array $order): static
    {
        $this->childrenOrder = $order;

        return $this;
    }

    /**
     * Set child blocks using the blocks() method.
     */
    public function blocks(array $children): static
    {
        $this->children = static::normalizeChildren($children);

        return $this;
    }

    /**
     * Set child blocks using the children() method (alias for blocks()).
     */
    public function children(array $children): static
    {
        return $this->blocks($children);
    }

    /**
     * Normalize children array by converting class names to instances.
     *
     * @param  array  $children  Raw children array (may contain class names, instances, or arrays)
     * @return array Normalized children array (instances and arrays)
     */
    protected static function normalizeChildren(array $children): array
    {
        return array_map(function ($item) {
            // If it's a class string that exists and extends PresetChild
            if (is_string($item) && class_exists($item)) {
                if (is_subclass_of($item, self::class)) {
                    // Call ::make() to instantiate (type will be auto-derived)
                    return $item::make();
                }
            }

            // Otherwise return as-is (PresetChild instance or array)
            return $item;
        }, $children);
    }

    /**
     * Append a single child block to this preset child.
     */
    public function addChild(self|array|string $child): static
    {
        $normalized = static::normalizeChildren([$child]);
        $this->children[] = $normalized[0];

        return $this;
    }

    /**
     * Append multiple child blocks to this preset child.
     */
    public function addChildren(array $children): static
    {
        $normalized = static::normalizeChildren($children);
        foreach ($normalized as $child) {
            $this->children[] = $child;
        }

        return $this;
    }

    /**
     * Append a single child block (alias for addChild()).
     */
    public function addBlock(self|array|string $child): static
    {
        return $this->addChild($child);
    }

    /**
     * Append multiple child blocks (alias for addChildren()).
     */
    public function addBlocks(array $children): static
    {
        return $this->addChildren($children);
    }

    /**
     * Merge additional properties into existing properties.
     */
    public function mergeProperties(array $properties): static
    {
        $this->properties = array_merge($this->properties, $properties);

        return $this;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
        ];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if (! empty($this->properties)) {
            $data['properties'] = $this->properties;
        }

        if ($this->static) {
            $data['static'] = $this->static;
        }

        if ($this->ghost) {
            $data['ghost'] = $this->ghost;
        }

        if ($this->repeated) {
            $data['repeated'] = $this->repeated;
        }

        if (! empty($this->children)) {
            $data['children'] = array_map(function ($child) {
                if ($child instanceof self) {
                    return $child->toArray();
                }

                return $child;
            }, $this->children);

            if ($this->childrenOrder !== null) {
                $data['order'] = $this->childrenOrder;
            }
        }

        return $data;
    }

    /**
     * JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
