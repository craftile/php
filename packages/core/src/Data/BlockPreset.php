<?php

namespace Craftile\Core\Data;

use JsonSerializable;

/**
 * Represents a block preset configuration.
 * Presets define predefined block configurations with custom properties and nested children.
 */
class BlockPreset implements JsonSerializable
{
    protected string $name;

    protected ?string $description = null;

    protected ?string $icon = null;

    protected ?string $category = null;

    protected array $properties = [];

    protected array $children = [];

    /**
     * Create a new block preset instance.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Create a new block preset.
     */
    public static function make(string $name): self
    {
        return new self($name);
    }

    /**
     * Set the preset description.
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the preset icon.
     */
    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the preset category.
     */
    public function category(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Set the preset properties (overrides for the block's default properties).
     */
    public function properties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Set child blocks for this preset.
     */
    public function blocks(array $children): self
    {
        $this->children = $children;

        return $this;
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

        if (! empty($this->properties)) {
            $data['properties'] = $this->properties;
        }

        if (! empty($this->children)) {
            $data['children'] = array_map(function ($child) {
                if ($child instanceof PresetBlock) {
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
