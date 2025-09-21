<?php

namespace Craftile\Core\Concerns;

trait ContextAware
{
    protected array $context = [];

    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}
