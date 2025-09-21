<?php

namespace Craftile\Core\Concerns;

/**
 * Trait providing default implementations for BlockInterface methods.
 * Uses class name and static properties to generate metadata.
 */
trait IsBlock
{
    /**
     * Generate slug from class name.
     */
    public static function slug(): string
    {
        $class = (new \ReflectionClass(static::class))->getShortName();

        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));

        return str_replace('_', '-', $snakeCase);
    }

    /**
     * Generate human-readable name from class name.
     */
    public static function name(): string
    {
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
