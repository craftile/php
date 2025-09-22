<?php

namespace Craftile\Laravel;

use Craftile\Core\Data\BlockSchema;

class Craftile
{
    protected $previewDetector = null;

    protected ?bool $previewModeCache = null;

    public function __construct(
        protected BlockSchemaRegistry $schemaRegistry,
    ) {}

    /**
     * Register a custom preview mode detector.
     */
    public function detectPreviewUsing(callable $detector): void
    {
        $this->previewDetector = $detector;
        $this->previewModeCache = null;
    }

    /**
     * Check if currently in preview mode.
     */
    public function inPreview(): bool
    {
        if ($this->previewModeCache !== null) {
            return $this->previewModeCache;
        }

        if ($this->previewDetector) {
            return $this->previewModeCache = call_user_func($this->previewDetector);
        }

        // Fall back to default query parameter detection
        $parameter = config('craftile.preview.query_parameter', '_preview');

        return $this->previewModeCache = request()->has($parameter);
    }

    /**
     * Get block schema by type.
     */
    public function getBlockSchema(string $type): ?BlockSchema
    {
        return $this->schemaRegistry->get($type);
    }
}
