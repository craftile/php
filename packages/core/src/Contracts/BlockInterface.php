<?php

namespace Craftile\Core\Contracts;

/**
 * Interface that all Craftile blocks must implement.
 * Provides metadata for blocks.
 */
interface BlockInterface
{
    /**
     * Get the block's type identifier (e.g., "@visual/hero" or "my-block").
     */
    public static function type(): string;

    /**
     * Get the block's unique slug identifier (slugified version of type).
     */
    public static function slug(): string;

    /**
     * Get the human-readable name of the block.
     */
    public static function name(): string;

    /**
     * Get the description of what this block does.
     */
    public static function description(): string;

    /**
     * Define the properties/fields this block accepts.
     * Should return an array of property definitions.
     */
    public static function properties(): array;

    /**
     * Define which child block types this block accepts.
     * Return ['*'] to accept any blocks, or [] to accept none.
     */
    public static function accepts(): array;

    /**
     * Get the icon svg for this block.
     */
    public static function icon(): string;

    /**
     * Get the category this block belongs to.
     */
    public static function category(): string;

    /**
     * Get the wrapper Emmet syntax for this block.
     * Return null for no wrapper.
     */
    public static function wrapper(): ?string;

    /**
     * Render the block with its current data (and context).
     * Implementation can return HTML string or framework-specific response.
     */
    public function render(): mixed;
}
