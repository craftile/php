<?php

namespace Craftile\Core\Services;

/**
 * Utility for flattening nested block structures to standard flat format.
 */
class BlockFlattener
{
    protected array $idMappings = [];

    public function flattenNestedStructure(array $templateData): array
    {
        $this->idMappings = [];

        $flatBlocks = [];
        $originalBlocks = $templateData['blocks'] ?? [];

        foreach ($originalBlocks as $blockId => $blockData) {
            if (! isset($blockData['id'])) {
                $blockData['id'] = $blockId;
            }

            $this->extractBlockRecursively($blockData, $flatBlocks, null);
        }

        return [
            'blocks' => $flatBlocks,
            'regions' => $this->extractRegions($templateData),
            '_idMappings' => $this->idMappings,
        ];
    }

    /**
     * Check if a block structure contains nested children (objects/arrays with block definitions).
     */
    public function hasNestedStructure(array $templateData): bool
    {
        if (! isset($templateData['blocks']) || ! is_array($templateData['blocks'])) {
            return false;
        }

        foreach ($templateData['blocks'] as $block) {
            if ($this->hasNestedChildren($block)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract regions from template data.
     */
    protected function extractRegions(array $templateData): array
    {
        if (isset($templateData['name']) && isset($templateData['order'])) {
            return [
                [
                    'name' => $templateData['name'],
                    'blocks' => $templateData['order'],
                ],
            ];
        }

        if (isset($templateData['regions'])) {
            return $templateData['regions'];
        }

        // Default: single region with all blocks
        $blockIds = array_keys($templateData['blocks'] ?? []);

        return [
            [
                'name' => 'main',
                'blocks' => $blockIds,
            ],
        ];
    }

    /**
     * Recursively extract blocks from nested structure.
     */
    protected function extractBlockRecursively(array $blockData, array &$flatBlocks, ?string $parentId): void
    {
        if (! isset($blockData['id']) || ! isset($blockData['type'])) {
            throw new \InvalidArgumentException('Block must have both "id" and "type" properties');
        }

        $uniqueId = $this->generateUniqueId($parentId, $blockData['id']);

        $flatBlock = $blockData;
        $flatBlock['id'] = $uniqueId;
        $flatBlock['parentId'] = $parentId;
        $flatBlock['children'] = [];

        // Set semantic ID for static blocks if not already set
        if (($flatBlock['static'] ?? false) && ! isset($flatBlock['semanticId'])) {
            $flatBlock['semanticId'] = $blockData['id'];
        }

        $children = $blockData['children'] ?? null;

        if (! empty($children) && $this->isNestedChildren($children)) {
            $childBlocks = $this->extractChildBlocks($children);
            $order = $blockData['order'] ?? array_keys($childBlocks);

            $uniqueChildIds = [];
            foreach ($order as $childLocalId) {
                $childUniqueId = $this->generateUniqueId($uniqueId, $childLocalId);
                $uniqueChildIds[] = $childUniqueId;
            }

            foreach ($childBlocks as $childBlock) {
                $this->extractBlockRecursively($childBlock, $flatBlocks, $uniqueId);
            }

            $flatBlock['children'] = $uniqueChildIds;
        } elseif (! empty($children) && is_array($children)) {
            // These are just ID references, keep as-is
            $flatBlock['children'] = $children;
        }

        $flatBlocks[$uniqueId] = $flatBlock;
    }

    /**
     * Check if a block has nested children (not just ID references).
     */
    protected function hasNestedChildren(array $block): bool
    {
        $children = $block['children'] ?? null;

        return $this->isNestedChildren($children);
    }

    /**
     * Check if children/blocks array contains nested block definitions.
     */
    protected function isNestedChildren($children): bool
    {
        if (! is_array($children) || empty($children)) {
            return false;
        }

        $firstChild = reset($children);

        return is_array($firstChild) && (isset($firstChild['id']) || isset($firstChild['type']));
    }

    /**
     * Extract child blocks from nested children structure.
     */
    protected function extractChildBlocks($children): array
    {
        if (isset($children[0])) {
            // Array format: [{ id: "child1", ... }, { id: "child2", ... }]
            $blocks = [];
            foreach ($children as $child) {
                if (! isset($child['id'])) {
                    throw new \InvalidArgumentException('Child block in array format must have "id" property');
                }
                $blocks[$child['id']] = $child;
            }

            return $blocks;
        }

        // Object format: { "child1": { id: "child1", ... }, "child2": { ... } }
        foreach ($children as $childId => $childBlock) {
            // Validate that key matches block ID if ID is specified
            if (isset($childBlock['id']) && $childBlock['id'] !== $childId) {
                throw new \InvalidArgumentException("Child block key '{$childId}' does not match block ID '{$childBlock['id']}'");
            }

            // Set ID if not specified
            if (! isset($childBlock['id'])) {
                $children[$childId]['id'] = $childId;
            }
        }

        return $children;
    }

    /**
     * Generate a unique ID for nested blocks.
     */
    protected function generateUniqueId(?string $parentId, string $childLocalId): string
    {
        // For root level blocks, use original ID
        if (empty($parentId)) {
            return $childLocalId;
        }

        // Generate unique ID using pattern: childLocalId_{contextHash}
        $contextHash = substr(hash('xxh128', $parentId.'.'.$childLocalId), 0, 8);
        $uniqueId = "{$childLocalId}_{$contextHash}";

        // Store mapping for template resolution
        $this->idMappings["{$parentId}.{$childLocalId}"] = $uniqueId;

        return $uniqueId;
    }

    /**
     * Get ID mappings from last flattening operation.
     */
    public function getIdMappings(): array
    {
        return $this->idMappings;
    }

    /**
     * Clear ID mappings.
     */
    public function clearMappings(): void
    {
        $this->idMappings = [];
    }
}
