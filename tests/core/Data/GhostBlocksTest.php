<?php

use Craftile\Core\Data\BlockData;
use Craftile\Core\Data\PresetChild;

describe('Ghost Blocks', function () {
    describe('BlockData ghost property', function () {
        it('has ghost property defaulting to false', function () {
            $blockData = BlockData::make([
                'id' => 'test-1',
                'type' => 'text',
            ]);

            expect($blockData->ghost)->toBeFalse();
        });

        it('can be created with ghost=true', function () {
            $blockData = BlockData::make([
                'id' => 'test-1',
                'type' => 'text',
                'ghost' => true,
            ]);

            expect($blockData->ghost)->toBeTrue();
        });

        it('includes ghost in toArray()', function () {
            $blockData = BlockData::make([
                'id' => 'test-1',
                'type' => 'text',
                'ghost' => true,
            ]);

            $array = $blockData->toArray();

            expect($array)->toHaveKey('ghost');
            expect($array['ghost'])->toBeTrue();
        });

        it('includes ghost=false in toArray()', function () {
            $blockData = BlockData::make([
                'id' => 'test-1',
                'type' => 'text',
            ]);

            $array = $blockData->toArray();

            expect($array)->toHaveKey('ghost');
            expect($array['ghost'])->toBeFalse();
        });

        it('has isGhost() helper method', function () {
            $ghostBlock = BlockData::make([
                'id' => 'test-1',
                'type' => 'text',
                'ghost' => true,
            ]);

            $normalBlock = BlockData::make([
                'id' => 'test-2',
                'type' => 'text',
            ]);

            expect($ghostBlock->isGhost())->toBeTrue();
            expect($normalBlock->isGhost())->toBeFalse();
        });

        it('includes ghost in JSON serialization', function () {
            $blockData = BlockData::make([
                'id' => 'test-1',
                'type' => 'text',
                'ghost' => true,
            ]);

            $json = json_decode(json_encode($blockData), true);

            expect($json)->toHaveKey('ghost');
            expect($json['ghost'])->toBeTrue();
        });
    });

    describe('PresetChild ghost() method', function () {
        it('can mark preset child as ghost', function () {
            $child = PresetChild::make('meta-title')
                ->ghost();

            $array = $child->toArray();

            expect($array)->toHaveKey('ghost');
            expect($array['ghost'])->toBeTrue();
        });

        it('can set ghost to false explicitly', function () {
            $child = PresetChild::make('text')
                ->ghost(false);

            $array = $child->toArray();

            expect($array)->not->toHaveKey('ghost');
        });

        it('defaults to not including ghost in array', function () {
            $child = PresetChild::make('text');

            $array = $child->toArray();

            expect($array)->not->toHaveKey('ghost');
        });

        it('supports fluent API with ghost()', function () {
            $child = PresetChild::make('meta-description')
                ->id('meta-desc')
                ->ghost()
                ->properties(['content' => 'Page description']);

            $array = $child->toArray();

            expect($array['id'])->toBe('meta-desc');
            expect($array['ghost'])->toBeTrue();
            expect($array['properties']['content'])->toBe('Page description');
        });

        it('can chain ghost() with static()', function () {
            $child = PresetChild::make('meta')
                ->ghost()
                ->static();

            $array = $child->toArray();

            expect($array['ghost'])->toBeTrue();
            expect($array['static'])->toBeTrue();
        });
    });

    describe('Ghost blocks in build() method', function () {
        it('can define ghost children in build()', function () {
            $preset = new class extends \Craftile\Core\Data\BlockPreset
            {
                protected function getName(): string
                {
                    return 'SEO Preset';
                }

                protected function build(): void
                {
                    $this->blocks([
                        PresetChild::make('meta-title')
                            ->ghost()
                            ->properties(['content' => '']),
                        PresetChild::make('meta-description')
                            ->ghost()
                            ->properties(['content' => '']),
                        PresetChild::make('hero')
                            ->properties(['title' => '']),
                    ]);
                }
            };

            $instance = $preset::make();
            $array = $instance->toArray();

            expect($array['children'])->toHaveCount(3);
            expect($array['children'][0]['ghost'])->toBeTrue();
            expect($array['children'][1]['ghost'])->toBeTrue();
            expect($array['children'][2])->not->toHaveKey('ghost');
        });

        it('can mix ghost and normal children', function () {
            $preset = new class extends \Craftile\Core\Data\BlockPreset
            {
                protected function getName(): string
                {
                    return 'Mixed Preset';
                }

                protected function build(): void
                {
                    $this->blocks([
                        PresetChild::make('visible-block'),
                        PresetChild::make('ghost-block')->ghost(),
                        PresetChild::make('another-visible'),
                    ]);
                }
            };

            $instance = $preset::make();
            $array = $instance->toArray();

            expect($array['children'])->toHaveCount(3);
            expect($array['children'][0])->not->toHaveKey('ghost');
            expect($array['children'][1]['ghost'])->toBeTrue();
            expect($array['children'][2])->not->toHaveKey('ghost');
        });
    });
});
