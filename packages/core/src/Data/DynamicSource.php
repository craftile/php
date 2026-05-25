<?php

namespace Craftile\Core\Data;

/**
 * Represents a dynamic source that needs to be resolved from context.
 */
class DynamicSource
{
    public function __construct(
        public readonly string $path,
        public readonly mixed $context,
        public readonly array $schema = [],
    ) {}
}
