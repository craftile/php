<?php

declare(strict_types=1);

use Craftile\Core\Services\BlockFlattener;

describe('BlockFlattener', function () {
    beforeEach(function () {
        $this->flattener = new BlockFlattener;
    });

    it('can detect nested structure', function () {
        $nestedTemplate = [
            'blocks' => [
                'parent' => [
                    'id' => 'parent',
                    'type' => 'container',
                    'children' => [
                        ['id' => 'child1', 'type' => 'text'],
                        ['id' => 'child2', 'type' => 'image'],
                    ],
                ],
            ],
        ];

        expect($this->flattener->hasNestedStructure($nestedTemplate))->toBeTrue();
    });

    it('can detect flat structure', function () {
        $flatTemplate = [
            'blocks' => [
                'block1' => ['id' => 'block1', 'type' => 'text'],
                'block2' => ['id' => 'block2', 'type' => 'image'],
            ],
        ];

        expect($this->flattener->hasNestedStructure($flatTemplate))->toBeFalse();
    });

    it('handles empty template data', function () {
        expect($this->flattener->hasNestedStructure([]))->toBeFalse();
        expect($this->flattener->hasNestedStructure(['blocks' => []]))->toBeFalse();
    });

    it('can flatten simple nested structure', function () {
        $nested = [
            'blocks' => [
                'parent' => [
                    'id' => 'parent',
                    'type' => 'container',
                    'children' => [
                        ['id' => 'child1', 'type' => 'text'],
                        ['id' => 'child2', 'type' => 'image'],
                    ],
                ],
            ],
        ];

        $result = $this->flattener->flattenNestedStructure($nested);

        expect($result)->toHaveKey('blocks');
        expect($result)->toHaveKey('regions');

        $blocks = $result['blocks'];
        expect($blocks)->toHaveCount(3); // parent + 2 children

        expect($blocks['parent'])->toHaveKey('id', 'parent');
        expect($blocks['parent'])->toHaveKey('type', 'container');
        expect($blocks['parent'])->toHaveKey('children');
        expect($blocks['parent']['children'])->toHaveCount(2);

        $childIds = $blocks['parent']['children'];
        expect($blocks)->toHaveKey($childIds[0]);
        expect($blocks)->toHaveKey($childIds[1]);
    });

    it('generates unique IDs for nested children', function () {
        $nested = [
            'blocks' => [
                'parent1' => [
                    'id' => 'parent1',
                    'type' => 'container',
                    'children' => [['id' => 'child', 'type' => 'text']],
                ],
                'parent2' => [
                    'id' => 'parent2',
                    'type' => 'container',
                    'children' => [['id' => 'child', 'type' => 'text']], // Same local ID
                ],
            ],
        ];

        $result = $this->flattener->flattenNestedStructure($nested);
        $blocks = $result['blocks'];

        // Should have 4 blocks: 2 parents + 2 children with unique IDs
        expect($blocks)->toHaveCount(4);

        $child1Id = $blocks['parent1']['children'][0];
        $child2Id = $blocks['parent2']['children'][0];

        expect($child1Id)->not->toBe($child2Id); // Different unique IDs
        expect($blocks)->toHaveKey($child1Id);
        expect($blocks)->toHaveKey($child2Id);
    });

    it('handles object format children', function () {
        $nested = [
            'blocks' => [
                'parent' => [
                    'id' => 'parent',
                    'type' => 'container',
                    'children' => [
                        'child1' => ['id' => 'child1', 'type' => 'text'],
                        'child2' => ['id' => 'child2', 'type' => 'image'],
                    ],
                ],
            ],
        ];

        $result = $this->flattener->flattenNestedStructure($nested);
        $blocks = $result['blocks'];

        expect($blocks)->toHaveCount(3);
        expect($blocks['parent']['children'])->toHaveCount(2);
    });

    it('preserves parent-child relationships', function () {
        $nested = [
            'blocks' => [
                'parent' => [
                    'id' => 'parent',
                    'type' => 'container',
                    'children' => [
                        ['id' => 'child', 'type' => 'text'],
                    ],
                ],
            ],
        ];

        $result = $this->flattener->flattenNestedStructure($nested);
        $blocks = $result['blocks'];

        $childId = $blocks['parent']['children'][0];
        $childBlock = $blocks[$childId];

        expect($childBlock)->toHaveKey('parentId', 'parent');
        expect($blocks['parent'])->toHaveKey('parentId', null);
    });

    it('handles static blocks with semantic IDs', function () {
        $nested = [
            'blocks' => [
                'static-block' => [
                    'id' => 'static-block',
                    'type' => 'header',
                    'static' => true,
                ],
            ],
        ];

        $result = $this->flattener->flattenNestedStructure($nested);
        $blocks = $result['blocks'];

        expect($blocks['static-block'])->toHaveKey('semanticId', 'static-block');
        expect($blocks['static-block'])->toHaveKey('static', true);
    });

    it('handles custom regions', function () {
        $template = [
            'blocks' => [
                'block1' => ['id' => 'block1', 'type' => 'text'],
            ],
            'regions' => [
                ['name' => 'header', 'blocks' => ['block1']],
                ['name' => 'footer', 'blocks' => []],
            ],
        ];

        $result = $this->flattener->flattenNestedStructure($template);

        expect($result['regions'])->toBe($template['regions']);
    });

    it('validates block structure', function () {
        $invalidNested = [
            'blocks' => [
                'invalid' => [
                    'id' => 'invalid',
                    // Missing required 'type'
                ],
            ],
        ];

        expect(fn () => $this->flattener->flattenNestedStructure($invalidNested))
            ->toThrow(\InvalidArgumentException::class, 'Block must have both "id" and "type" properties');
    });

    it('stores ID mappings', function () {
        $nested = [
            'blocks' => [
                'parent' => [
                    'id' => 'parent',
                    'type' => 'container',
                    'children' => [
                        ['id' => 'child', 'type' => 'text'],
                    ],
                ],
            ],
        ];

        $result = $this->flattener->flattenNestedStructure($nested);

        expect($result)->toHaveKey('_idMappings');
        expect($this->flattener->getIdMappings())->not->toBeEmpty();
    });

    it('can clear mappings', function () {
        $this->flattener->flattenNestedStructure([
            'blocks' => [
                'parent' => [
                    'id' => 'parent',
                    'type' => 'container',
                    'children' => [
                        ['id' => 'child', 'type' => 'text'],
                    ],
                ],
            ],
        ]);

        expect($this->flattener->getIdMappings())->not->toBeEmpty();

        $this->flattener->clearMappings();

        expect($this->flattener->getIdMappings())->toBe([]);
    });

    it('handles deeply nested structures', function () {
        $nested = [
            'blocks' => [
                'level1' => [
                    'id' => 'level1',
                    'type' => 'container',
                    'children' => [
                        [
                            'id' => 'level2',
                            'type' => 'container',
                            'children' => [
                                ['id' => 'level3', 'type' => 'text'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->flattener->flattenNestedStructure($nested);
        $blocks = $result['blocks'];

        expect($blocks)->toHaveCount(3); // All three levels should be flattened

        // Check the hierarchy is preserved
        $level2Id = $blocks['level1']['children'][0];
        $level3Id = $blocks[$level2Id]['children'][0];

        expect($blocks[$level2Id])->toHaveKey('parentId', 'level1');
        expect($blocks[$level3Id])->toHaveKey('parentId', $level2Id);
    });
});
