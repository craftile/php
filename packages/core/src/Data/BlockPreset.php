<?php

namespace Craftile\Core\Data;

use JsonSerializable;

/**
 * Represents a block preset configuration.
 * Presets define predefined block configurations with custom properties and nested children.
 *
 * @phpstan-consistent-constructor
 */
class BlockPreset implements JsonSerializable
{
    protected string $name;

    protected ?string $description = null;

    protected ?string $icon = null;

    protected ?string $category = null;

    protected ?string $previewImageUrl = null;

    protected array $properties = [];

    protected array $children = [];

    /**
     * Create a new block preset instance.
     */
    public function __construct(?string $name = null)
    {
        $this->build();

        // Set name with priority: constructor param > build() set value > getName()
        $this->name = $name ?? $this->name ?? $this->getName();
    }

    /**
     * Create a new block preset.
     */
    public static function make(?string $name = null): static
    {
        return new static($name);
    }

    /**
     * Get default preset name.
     * Can be overridden in subclasses to provide a custom name.
     */
    protected function getName(): string
    {
        $class = (new \ReflectionClass(static::class))->getShortName();

        $class = preg_replace('/Preset$/', '', $class);

        $words = preg_replace('/(?<!^)[A-Z]/', ' $0', $class);

        return trim($words);
    }

    /**
     * Get the block type this preset belongs to.
     * Override in subclasses to specify the target block type for discovery.
     */
    public static function getType(): ?string
    {
        return null;
    }

    /**
     * Build the preset configuration.
     * Override this method in subclasses to define preset structure.
     */
    protected function build(): void
    {
        // Base implementation does nothing
    }

    /**
     * Set the preset name.
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the preset description.
     */
    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the preset icon.
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the preset category.
     */
    public function category(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Set the preset preview image URL.
     */
    public function previewImageUrl(string $previewImageUrl): static
    {
        $this->previewImageUrl = $previewImageUrl;

        return $this;
    }

    /**
     * Set the preset properties (overrides for the block's default properties).
     */
    public function properties(array $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Set child blocks for this preset.
     */
    public function blocks(array $children): static
    {
        $this->children = static::normalizeChildren($children);

        return $this;
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
                if (is_subclass_of($item, PresetChild::class)) {
                    // Call ::make() to instantiate (type will be auto-derived)
                    return $item::make();
                }
            }

            // Otherwise return as-is (PresetChild instance or array)
            return $item;
        }, $children);
    }

    /**
     * Append a single child block to this preset.
     */
    public function addBlock(PresetChild|array|string $block): static
    {
        $normalized = static::normalizeChildren([$block]);
        $this->children[] = $normalized[0];

        return $this;
    }

    /**
     * Append multiple child blocks to this preset.
     */
    public function addBlocks(array $blocks): static
    {
        $normalized = static::normalizeChildren($blocks);
        foreach ($normalized as $block) {
            $this->children[] = $block;
        }

        return $this;
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
     * Convert preset to a PresetChild for use in another preset's children.
     *
     * @param  string|null  $type  Block type (if null, uses getType())
     *
     * @throws \LogicException If no type provided and getType() returns null
     */
    public static function asChild(?string $type = null): PresetChild
    {
        $instance = new static;

        $blockType = $type ?? static::getType();

        if ($blockType === null) {
            throw new \LogicException(
                'Type must be provided via asChild($type) or by overriding getType() method in '.static::class
            );
        }

        $child = PresetChild::make($blockType)
            ->properties($instance->properties)
            ->children($instance->children);

        if (isset($instance->name)) {
            $child->name($instance->name);
        }

        return $child;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->icon !== null) {
            $data['icon'] = $this->icon;
        }

        if ($this->category !== null) {
            $data['category'] = $this->category;
        }

        if ($this->previewImageUrl !== null) {
            $data['previewImageUrl'] = $this->previewImageUrl;
        }

        if (! empty($this->properties)) {
            $data['properties'] = $this->properties;
        }

        if (! empty($this->children)) {
            $data['children'] = array_map(function ($child) {
                if ($child instanceof PresetChild) {
                    return $child->toArray();
                }

                return $child;
            }, $this->children);
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
