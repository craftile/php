<?php

namespace Craftile\Core\Data;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Container for block property values.
 *
 * @template TKey of string
 * @template TValue
 */
class PropertyBag implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * All of the property values.
     *
     * @var array<TKey, TValue>
     */
    protected array $values = [];

    /**
     * All of the property schemas.
     *
     * @var array<TKey, TValue>
     */
    protected array $schemas = [];

    /**
     * Resolved values cache.
     *
     * @var array<TKey, TValue>
     */
    protected array $resolved = [];

    /**
     * Context for resolving dynamic sources.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Create a new PropertyBag instance.
     *
     * @param  iterable<TKey, TValue>  $values
     * @param  iterable<TKey, TValue>  $schemas
     */
    public function __construct(iterable $values = [], iterable $schemas = [])
    {
        foreach ($values as $key => $value) {
            $this->values[$key] = $value;
        }

        foreach ($schemas as $key => $schema) {
            $this->schemas[$key] = $schema;
        }
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * Get a value by key, with transformation applied.
     *
     * @param  TKey  $key
     * @return TValue|mixed
     */
    public function get(string $key): mixed
    {
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        if (! array_key_exists($key, $this->values)) {
            return null;
        }

        $value = $this->values[$key];
        $schema = $this->schemas[$key] ?? null;

        $type = null;
        $isResponsive = false;
        if (is_array($schema)) {
            $type = $schema['type'] ?? null;
            $isResponsive = $schema['responsive'] ?? false;
        }

        if ($isResponsive && is_array($value) && isset($value['_default'])) {
            $transformedBreakpoints = [];
            foreach ($value as $breakpoint => $breakpointValue) {
                $transformedBreakpoints[$breakpoint] = $this->transformValue($breakpointValue, $type);
            }

            $responsiveValue = new ResponsiveValue($transformedBreakpoints, $type);
            $this->resolved[$key] = $responsiveValue;

            return $responsiveValue;
        }

        // Detect dynamic source (@ prefix) and create DynamicSource object
        if (is_string($value) && str_starts_with($value, '@')) {
            $path = substr($value, 1); // Remove @ prefix
            $default = is_array($schema) && isset($schema['default']) ? $schema['default'] : null;

            $value = new DynamicSource(
                path: $path,
                type: $type ?? 'text', // Fallback to 'text' if no type specified
                context: $this->context,
                default: $default
            );
            $type = '__dynamic_source__';
        }

        $transformedValue = $this->transformValue($value, $type);

        $this->resolved[$key] = $transformedValue;

        return $transformedValue;
    }

    /**
     * Check if a key exists.
     *
     * @param  TKey  $key
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * Get all values as array.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Get raw values without transformation.
     *
     * @return array<TKey, TValue>
     */
    public function raw(): array
    {
        return $this->values;
    }

    /**
     * Get only values for specified keys.
     *
     * @param  array<TKey>  $keys
     */
    public function only(array $keys): self
    {
        $result = [];
        $schemas = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->values[$key];
                if (isset($this->schemas[$key])) {
                    $schemas[$key] = $this->schemas[$key];
                }
            }
        }

        return new self($result, $schemas);
    }

    /**
     * Get all values except specified keys.
     *
     * @param  array<TKey>  $keys
     */
    public function except(array $keys): self
    {
        $result = $this->values;
        $schemas = $this->schemas;
        foreach ($keys as $key) {
            unset($result[$key]);
            unset($schemas[$key]);
        }

        return new self($result, $schemas);
    }

    /**
     * Transform a value based on its type.
     */
    protected function transformValue(mixed $value, ?string $type): mixed
    {
        return $value;
    }

    /**
     * Count the number of values.
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * Get an iterator for the values.
     *
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        foreach (array_keys($this->values) as $key) {
            yield $key => $this->get($key);
        }
    }

    /**
     * Convert to JSON serializable array.
     *
     * @return array<TKey, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }

    /**
     * Convert to array (useful for framework compatibility).
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->all();
    }

    /**
     * Set the context for resolving dynamic sources.
     *
     * @param  array<string, mixed>  $context
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        // Clear resolved cache when context changes
        $this->resolved = [];

        return $this;
    }
}
