<?php

namespace Craftile\Core\Data;

use Stringable;

/**
 * Represents a responsive value that can vary across different breakpoints.
 */
class ResponsiveValue implements Stringable
{
    /**
     * All breakpoint values including _default.
     */
    protected array $values;

    /**
     * The default value to use when no breakpoint matches.
     */
    protected mixed $defaultValue;

    /**
     * The property type (for transformation purposes).
     */
    protected ?string $type;

    /**
     * Create a new ResponsiveValue instance.
     *
     * @param  array  $values  Array of breakpoint => value pairs, must include '_default'
     * @param  string|null  $type  The property type
     */
    public function __construct(array $values, ?string $type = null)
    {
        $this->values = $values;
        $this->defaultValue = $values['_default'] ?? null;
        $this->type = $type;
    }

    /**
     * Magic getter to access breakpoint-specific values.
     *
     * @param  string  $breakpoint  The breakpoint name (e.g., 'md', 'lg', 'xl')
     * @return mixed The value for the breakpoint, or null
     */
    public function __get(string $breakpoint): mixed
    {
        return $this->values[$breakpoint] ?? null;
    }

    /**
     * Get the default value.
     */
    public function value(): mixed
    {
        return $this->defaultValue;
    }

    /**
     * Convert to string (returns default value).
     */
    public function __toString(): string
    {
        return (string) $this->defaultValue;
    }

    /**
     * Get all breakpoint values.
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Check if a breakpoint value exists.
     */
    public function has(string $breakpoint): bool
    {
        return isset($this->values[$breakpoint]);
    }

    /**
     * Get the property type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get value for specific breakpoint with custom fallback.
     */
    public function get(string $breakpoint, mixed $fallback = null): mixed
    {
        return $this->values[$breakpoint] ?? $this->defaultValue ?? $fallback;
    }
}
