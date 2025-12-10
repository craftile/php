<?php

namespace Craftile\Core\Concerns;

trait ContextAware
{
    protected array $context = [];

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Get a value from the context by key.
     *
     * @param  string  $key  The context key to retrieve
     * @param  mixed  $default  Default value if key doesn't exist
     * @return mixed The context value or default
     */
    public function context(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }
}
