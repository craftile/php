<?php

namespace Craftile\Core\Data;

/**
 * Represents a dynamic source that needs to be resolved from context.
 */
class DynamicSource
{
    public function __construct(
        public readonly string $path,
        public readonly string $type,
        public readonly mixed $context,
        public readonly mixed $default = null
    ) {}
}
