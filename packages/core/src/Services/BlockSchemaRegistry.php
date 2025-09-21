<?php

namespace Craftile\Core\Services;

use Craftile\Core\Data\BlockSchema;

/**
 * Registry for block schemas.
 */
class BlockSchemaRegistry
{
    /** @var array<string, BlockSchema> */
    protected array $schemas = [];

    /**
     * Register a block schema.
     */
    public function register(BlockSchema $schema): void
    {
        $this->schemas[$schema->type] = $schema;
    }

    /**
     * Get a schema by type.
     */
    public function getSchema(string $type): ?BlockSchema
    {
        return $this->schemas[$type] ?? null;
    }

    /**
     * Check if a schema exists.
     */
    public function hasSchema(string $type): bool
    {
        return isset($this->schemas[$type]);
    }

    /**
     * Get all registered schemas.
     *
     * @return array<string, BlockSchema>
     */
    public function getAllSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Get all registered schemas (alias for getAllSchemas).
     *
     * @return array<string, BlockSchema>
     */
    public function all(): array
    {
        return $this->getAllSchemas();
    }

    /**
     * Get a schema by type (alias for getSchema).
     */
    public function get(string $type): ?BlockSchema
    {
        return $this->getSchema($type);
    }

    /**
     * Get all registered block types.
     *
     * @return array<string>
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->schemas);
    }

    /**
     * Remove a schema.
     */
    public function removeSchema(string $type): void
    {
        unset($this->schemas[$type]);
    }

    /**
     * Clear all schemas.
     */
    public function clear(): void
    {
        $this->schemas = [];
    }
}
