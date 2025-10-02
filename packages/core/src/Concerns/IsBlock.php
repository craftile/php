<?php

namespace Craftile\Core\Concerns;

/**
 * Trait providing default implementations for BlockInterface methods.
 * Uses class name and static properties to generate metadata.
 */
trait IsBlock
{
    /**
     * Get block type (e.g., "@visual/hero" or "my-block").
     * Defaults to kebab-cased class name if not defined.
     */
    public static function type(): string
    {
        if (isset(static::$type)) {
            return static::$type;
        }

        $class = (new \ReflectionClass(static::class))->getShortName();

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $class));
    }

    /**
     * Get block slug (slugified version of type).
     * Converts "@visual/hero" to "visual-hero".
     */
    public static function slug(): string
    {
        if (isset(static::$slug)) {
            return static::$slug;
        }

        $slug = static::type();
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Generate human-readable name from class name.
     */
    public static function name(): string
    {
        if (isset(static::$name)) {
            return static::$name;
        }

        $class = (new \ReflectionClass(static::class))->getShortName();

        $words = preg_replace('/(?<!^)[A-Z]/', ' $0', $class);

        return trim($words);
    }

    /**
     * Get block description from static property.
     */
    public static function description(): string
    {
        return static::$description ?? '';
    }

    /**
     * Get block properties from static property.
     */
    public static function properties(): array
    {
        return static::$properties ?? [];
    }

    /**
     * Get accepted child types from static property.
     */
    public static function accepts(): array
    {
        return static::$accepts ?? [];
    }

    /**
     * Get block icon from static property.
     */
    public static function icon(): string
    {
        return static::$icon ?? '';
    }

    /**
     * Get block category from static property.
     */
    public static function category(): string
    {
        return static::$category ?? '';
    }
}
