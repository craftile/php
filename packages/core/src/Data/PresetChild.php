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
    protected string $type;

    protected ?string $id = null;

    protected ?string $name = null;

    protected array $properties = [];

    protected bool $static = false;

    protected array $children = [];

    /**
     * Create a new preset child instance.
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * Create a new preset child.
     */
    public static function make(string $type): static
    {
        return new static($type);
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
     * Set child blocks using the blocks() method.
     */
    public function blocks(array $children): static
    {
        $this->children = $children;

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

        if (! empty($this->children)) {
            $data['children'] = array_map(function ($child) {
                if ($child instanceof self) {
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
