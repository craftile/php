<?php

namespace Craftile\Core\Data;

use Craftile\Core\Contracts\BlockInterface;
use JsonSerializable;

/**
 * Represents a block schema definition with metadata and validation rules.
 */
class BlockSchema implements JsonSerializable
{
    public string $type;

    public string $slug;

    public string $class;

    public string $name;

    public ?string $description;

    public ?string $icon;

    public ?string $category;

    public array $properties = [];

    public array $accepts = [];

    public ?string $wrapper;

    public ?string $previewImageUrl;

    public array $presets = [];

    public function __construct(
        string $type,
        string $slug,
        string $class,
        string $name,
        ?string $description = null,
        ?string $icon = null,
        ?string $category = null,
        array $properties = [],
        array $accepts = [],
        ?string $wrapper = null,
        ?string $previewImageUrl = null,
        array $presets = []
    ) {
        $this->type = $type;
        $this->slug = $slug;
        $this->class = $class;
        $this->name = $name;
        $this->description = $description;
        $this->icon = $icon;
        $this->category = $category;
        $this->properties = $properties;
        $this->accepts = $accepts;
        $this->wrapper = $wrapper;
        $this->previewImageUrl = $previewImageUrl;
        $this->presets = $presets;
    }

    /**
     * Create schema from BlockInterface class.
     */
    public static function fromClass(string $blockClass): self
    {
        if (! class_exists($blockClass)) {
            throw new \InvalidArgumentException("Block class {$blockClass} does not exist");
        }

        if (! is_subclass_of($blockClass, BlockInterface::class)) {
            throw new \InvalidArgumentException("Block class {$blockClass} must implement BlockInterface");
        }

        return new self(
            type: $blockClass::type(),
            slug: $blockClass::slug(),
            class: $blockClass,
            name: $blockClass::name(),
            description: $blockClass::description(),
            icon: $blockClass::icon(),
            category: $blockClass::category(),
            properties: $blockClass::properties(),
            accepts: $blockClass::accepts(),
            wrapper: $blockClass::wrapper(),
            previewImageUrl: $blockClass::previewImageUrl(),
            presets: $blockClass::presets()
        );
    }

    /**
     * Convert schema to array representation.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'slug' => $this->slug,
            'class' => $this->class,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'category' => $this->category,
            'properties' => array_map(function ($prop) {
                return $prop instanceof Property ? $prop->toArray() : $prop;
            }, $this->properties),
            'accepts' => $this->accepts,
            'wrapper' => $this->wrapper,
            'previewImageUrl' => $this->previewImageUrl,
            'presets' => array_map(function ($preset) {
                return $preset instanceof BlockPreset ? $preset->toArray() : $preset;
            }, $this->presets),
        ];
    }

    /**
     * Validate the schema itself.
     */
    public function validate(): void
    {
        if (empty($this->type)) {
            throw new \InvalidArgumentException('Block type cannot be empty');
        }

        if (! class_exists($this->class)) {
            throw new \InvalidArgumentException("Block class {$this->class} does not exist");
        }

        if (empty($this->name)) {
            throw new \InvalidArgumentException('Block label cannot be empty');
        }

        // Validate properties
        foreach ($this->properties as $property) {
            if (is_object($property) && method_exists($property, 'validate')) {
                $property->validate();
            }
        }
    }

    /**
     * JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
