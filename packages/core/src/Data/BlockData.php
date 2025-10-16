<?php

namespace Craftile\Core\Data;

use JsonSerializable;

/**
 * Represents a single block instance with its properties and metadata.
 */
class BlockData implements JsonSerializable
{
    protected mixed $resolveChildData;

    public readonly ?int $iteration;

    final public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly PropertyBag $properties,
        public readonly ?string $name = null,
        public readonly ?string $parentId = null,
        public readonly ?array $childrenIds = [],
        public readonly bool $disabled = false,
        public readonly bool $static = false,
        public readonly bool $repeated = false,
        public readonly bool $ghost = false,
        public readonly ?string $semanticId = null,
        public readonly ?int $index = null,
        mixed $resolveChildData = null,
    ) {
        $this->resolveChildData = $resolveChildData;
        $this->iteration = $index !== null ? $index + 1 : null;
    }

    /**
     * Create BlockData from array data.
     */
    public static function make(array $data, mixed $resolveChildData = null): static
    {
        $properties = self::createPropertyBag($data['properties'] ?? [], $data['type']);

        return new static(
            id: $data['id'],
            type: $data['type'],
            properties: $properties,
            name: $data['name'] ?? null,
            parentId: $data['parentId'] ?? null,
            childrenIds: $data['children'] ?? [],
            disabled: $data['disabled'] ?? false,
            static: $data['static'] ?? false,
            repeated: $data['repeated'] ?? false,
            ghost: $data['ghost'] ?? false,
            semanticId: $data['semanticId'] ?? null,
            index: $data['index'] ?? null,
            resolveChildData: $resolveChildData,
        );
    }

    protected static function createPropertyBag(array $properties, string $blockType): PropertyBag
    {
        return new PropertyBag($properties);
    }

    /**
     * Get a property value.
     */
    public function property(string $key): mixed
    {
        return $this->properties->get($key);
    }

    /**
     * Check if the block has children.
     */
    public function hasChildren(): bool
    {
        return ! empty($this->childrenIds);
    }

    /**
     * Get children count.
     */
    public function childrenCount(): int
    {
        return count($this->childrenIds ?? []);
    }

    /**
     * Check if block is enabled.
     */
    public function isEnabled(): bool
    {
        return ! $this->disabled;
    }

    /**
     * Check if block is static.
     */
    public function isStatic(): bool
    {
        return $this->static;
    }

    /**
     * Check if block is repeated.
     */
    public function isRepeated(): bool
    {
        return $this->repeated;
    }

    /**
     * Check if block is a ghost block (data-only, not rendered).
     */
    public function isGhost(): bool
    {
        return $this->ghost;
    }

    /**
     * Get child data if resolver is available.
     */
    public function getChildData(): mixed
    {
        if (is_callable($this->resolveChildData)) {
            return ($this->resolveChildData)();
        }

        return null;
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'properties' => $this->properties->toArray(),
            'parentId' => $this->parentId,
            'children' => $this->childrenIds,
            'disabled' => $this->disabled,
            'static' => $this->static,
            'repeated' => $this->repeated,
            'ghost' => $this->ghost,
            'semanticId' => $this->semanticId,
            'index' => $this->index,
            'iteration' => $this->iteration,
        ];
    }

    /**
     * JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
