<?php

namespace Craftile\Core\Data;

/**
 * Fluent API for building conditional visibility rules.
 *
 * Used to express visibility, validation, or dynamic configuration conditions.
 * Rules are serialized to JSON for frontend evaluation.
 *
 * @phpstan-consistent-constructor
 *
 * @example
 * ```php
 * $rule = Rule::make()
 *     ->when('layout_style', 'grid')
 *     ->whenIn('status', ['published', 'draft']);
 * ```
 */
class Rule
{
    protected string $logic;

    protected array $rules = [];

    public function __construct(string $logic = 'and')
    {
        $this->logic = $logic;
    }

    public static function make(): static
    {
        return new static;
    }

    /**
     * Add a condition to the rule.
     *
     * @param  string  $field  The field name to test
     * @param  mixed  $operatorOrValue  Operator or value (if operator is omitted, defaults to equals)
     * @param  mixed  $value  The value to compare against (optional if operator is omitted)
     */
    public function when(string $field, mixed $operatorOrValue, mixed $value = null): static
    {
        if ($value === null) {
            $operator = 'equals';
            $val = $operatorOrValue;
        } else {
            $operator = $this->normalizeOperator($operatorOrValue);
            $val = $value;
        }

        $this->rules[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $val,
        ];

        return $this;
    }

    /**
     * Add a "not equals" condition.
     */
    public function whenNot(string $field, mixed $value): static
    {
        return $this->addCondition($field, 'not_equals', $value);
    }

    /**
     * Add an "in array" condition.
     */
    public function whenIn(string $field, array $values): static
    {
        $this->rules[] = [
            'field' => $field,
            'operator' => 'in',
            'value' => $values,
        ];

        return $this;
    }

    /**
     * Add a "not in array" condition.
     */
    public function whenNotIn(string $field, array $values): static
    {
        $this->rules[] = [
            'field' => $field,
            'operator' => 'not_in',
            'value' => $values,
        ];

        return $this;
    }

    /**
     * Add a "greater than" condition.
     */
    public function whenGt(string $field, mixed $value): static
    {
        return $this->addCondition($field, 'greater_than', $value);
    }

    /**
     * Add a "less than" condition.
     */
    public function whenLt(string $field, mixed $value): static
    {
        return $this->addCondition($field, 'less_than', $value);
    }

    /**
     * Add a "truthy" condition (!!field === true).
     */
    public function whenTruthy(string $field): static
    {
        $this->rules[] = [
            'field' => $field,
            'operator' => 'truthy',
        ];

        return $this;
    }

    /**
     * Add a "falsy" condition (!!field === false).
     */
    public function whenFalsy(string $field): static
    {
        $this->rules[] = [
            'field' => $field,
            'operator' => 'falsy',
        ];

        return $this;
    }

    /**
     * Create an AND group of conditions.
     */
    public function and(callable $callback): static
    {
        $sub = new static('and');
        $callback($sub);

        $subRules = $sub->toArray();

        // If sub returns wrapped 'and', unwrap it to avoid double nesting
        if (isset($subRules['and'])) {
            $this->rules[] = ['and' => $subRules['and']];
        } else {
            $this->rules[] = ['and' => [$subRules]];
        }

        return $this;
    }

    /**
     * Create an OR group of conditions.
     */
    public function or(callable $callback): static
    {
        $sub = new static('and');
        $callback($sub);

        // If we already have rules, wrap everything in an OR
        if (! empty($this->rules)) {
            $this->rules = [
                'or' => array_merge(
                    $this->logic === 'and' ? [['and' => $this->rules]] : $this->rules,
                    [['and' => $sub->toArray()]]
                ),
            ];
            $this->logic = 'or';
        } else {
            $this->rules[] = ['and' => $sub->toArray()];
        }

        return $this;
    }

    /**
     * Add a condition with specified operator.
     */
    protected function addCondition(string $field, string $operator, mixed $value): static
    {
        $this->rules[] = [
            'field' => $field,
            'operator' => $this->normalizeOperator($operator),
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Normalize operator symbols to named operators.
     */
    protected function normalizeOperator(string $operator): string
    {
        return match ($operator) {
            '=', '==' => 'equals',
            '!=', '<>' => 'not_equals',
            '>', 'gt' => 'greater_than',
            '<', 'lt' => 'less_than',
            default => $operator,
        };
    }

    /**
     * Convert rule to array representation.
     */
    public function toArray(): array
    {
        // Handle special case where rules was set to ['or' => ...] structure
        if (isset($this->rules['or'])) {
            return $this->rules;
        }

        // Single rule - return unwrapped
        if (count($this->rules) === 1 && isset($this->rules[0]['field'])) {
            return $this->rules[0];
        }

        // Multiple rules with explicit logic
        if (count($this->rules) > 1) {
            return [$this->logic => $this->rules];
        }

        // Single grouped rule
        if (count($this->rules) === 1) {
            return $this->rules[0];
        }

        return [];
    }
}
