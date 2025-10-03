<?php

namespace Craftile\Core\Data;

use JsonSerializable;

/**
 * Represents a child block within a preset configuration.
 * Supports fluent API for building nested block structures.
 */
class PresetChild implements JsonSerializable
{
    protected string $type;

    protected ?string $id = null;

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
    public static function make(string $type): self
    {
        return new self($type);
    }

    /**
     * Set the block's semantic ID.
     */
    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the block's properties.
     */
    public function properties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Mark the block as static (non-editable).
     */
    public function static(bool $static = true): self
    {
        $this->static = $static;

        return $this;
    }

    /**
     * Set child blocks using the blocks() method.
     */
    public function blocks(array $children): self
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Set child blocks using the children() method (alias for blocks()).
     */
    public function children(array $children): self
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
