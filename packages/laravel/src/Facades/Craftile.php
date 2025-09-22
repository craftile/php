<?php

declare(strict_types=1);

namespace Craftile\Laravel\Facades;

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\Contracts\PropertyTransformerInterface;
use Craftile\Laravel\PropertyTransformerRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void discoverBlocksIn(string $namespace, string $directory)
 * @method static void detectPreviewUsing(callable $detector)
 * @method static bool inPreview()
 * @method static BlockSchema|null getBlockSchema(string $type)
 * @method static void registerPropertyTransformer(string $type, PropertyTransformerInterface|callable $transformer)
 * @method static PropertyTransformerRegistry getPropertyTransformerRegistry()
 *
 * @see \Craftile\Laravel\Craftile
 */
class Craftile extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'craftile';
    }
}