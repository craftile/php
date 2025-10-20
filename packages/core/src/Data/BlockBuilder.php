<?php

namespace Craftile\Core\Data;

/**
 * Builder for creating blocks in .craft.php templates.
 * Extends PresetChild to reuse all fluent methods.
 */
class BlockBuilder extends PresetChild
{
    /**
     * Create a new block builder for template use.
     *
     * @param  string  $id  Block ID (required)
     * @param  string|class-string<BlockPreset>  $type  Block type or preset class
     */
    public static function forTemplate(string $id, string $type): static
    {
        // If type is a BlockPreset class, convert to PresetChild
        if (class_exists($type) && is_subclass_of($type, BlockPreset::class)) {
            /** @var BlockPreset $type */
            return static::fromPresetChild($type::asChild(), $id);
        }

        // Create new instance with type
        $instance = new static($type);
        $instance->id = $id;

        return $instance;
    }

    /**
     * Create a BlockBuilder from an existing PresetChild.
     * Used when converting BlockPreset::asChild() to BlockBuilder.
     */
    public static function fromPresetChild(PresetChild $child, string $id): static
    {
        $instance = new static($child->type);

        // Copy all properties from PresetChild
        $instance->id = $id;
        $instance->name = $child->name;
        $instance->properties = $child->properties;
        $instance->static = $child->static;
        $instance->ghost = $child->ghost;
        $instance->repeated = $child->repeated;
        $instance->children = $child->children;
        $instance->childrenOrder = $child->childrenOrder;

        return $instance;
    }
}
