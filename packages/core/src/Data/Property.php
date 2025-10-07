<?php

namespace Craftile\Core\Data;

/**
 * @phpstan-consistent-constructor
 */
abstract class Property
{
    public function __construct(
        public string $id,
        public string $label,
        public array $meta = []
    ) {}

    public static function make(string $id, string $label): static
    {
        return new static($id, $label);
    }

    public function default(mixed $value): static
    {
        $this->meta['default'] = $value;

        return $this;
    }

    public function placeholder(string $value): static
    {
        $this->meta['placeholder'] = $value;

        return $this;
    }

    public function info(string $value): static
    {
        $this->meta['info'] = $value;

        return $this;
    }

    /**
     * Set conditional visibility rule for this property.
     *
     * @param  callable  $callback  Callback that receives a Rule instance
     *
     * @example
     * ```php
     * $property->visibleIf(fn($rule) => $rule->when('layout', 'grid'));
     * ```
     */
    public function visibleIf(callable $callback): static
    {
        $rule = new Rule;
        $callback($rule);

        $this->meta['visibleIf'] = $rule->toArray();

        return $this;
    }

    /**
     * Alias for visibleIf().
     *
     * @param  callable  $callback  Callback that receives a Rule instance
     *
     * @example
     * ```php
     * $property->visibleWhen(fn($rule) => $rule->when('layout', 'grid'));
     * ```
     */
    public function visibleWhen(callable $callback): static
    {
        return $this->visibleIf($callback);
    }

    /**
     * Mark this property as responsive, allowing different values per breakpoint.
     *
     *
     * @example
     * ```php
     * Text::make('title', 'Title')->responsive();
     * Number::make('columns', 'Columns')->responsive()->default(1);
     * ```
     */
    public function responsive(): static
    {
        $this->meta['responsive'] = true;

        return $this;
    }

    abstract public function type(): string;

    public function getId(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'type' => $this->type(),
            'label' => $this->label,
        ], $this->meta);
    }
}
