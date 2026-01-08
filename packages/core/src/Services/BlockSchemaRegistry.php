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
     * Store custom presets per block type.
     *
     * @var array<string, array>
     */
    protected array $customPresets = [];

    /**
     * Register a block schema.
     */
    public function register(BlockSchema $schema): void
    {
        $type = $schema->type;
        $this->schemas[$type] = $schema;

        if (isset($this->customPresets[$type])) {
            foreach ($this->customPresets[$type] as $preset) {
                $schema->registerPreset($preset);
            }
            unset($this->customPresets[$type]);
        }
    }

    /**
     * Register a custom preset to a block type.
     *
     * @param  string  $blockType  Block type identifier (e.g., 'container', 'text')
     * @param  \Craftile\Core\Data\BlockPreset|array|string  $preset  Preset instance, array, or class name
     */
    public function registerPreset(string $blockType, mixed $preset): void
    {
        if (isset($this->schemas[$blockType])) {
            $this->schemas[$blockType]->registerPreset($preset);
        } else {
            if (! isset($this->customPresets[$blockType])) {
                $this->customPresets[$blockType] = [];
            }
            $this->customPresets[$blockType][] = $preset;
        }
    }

    /**
     * Register multiple custom presets to a block type.
     *
     * @param  string  $blockType  Block type identifier
     * @param  array  $presets  Array of presets (instances, arrays, or class names)
     */
    public function registerPresets(string $blockType, array $presets): void
    {
        foreach ($presets as $preset) {
            $this->registerPreset($blockType, $preset);
        }
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
