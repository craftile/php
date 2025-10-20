<?php

namespace Craftile\Laravel\View;

use Craftile\Laravel\Exceptions\JsonViewException;
use Craftile\Laravel\Facades\Craftile;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Yaml\Yaml;

class JsonViewParser
{
    protected array $phpTemplateExtensions;

    /**
     * In-memory cache of parsed template data.
     */
    private static array $cache = [];

    public function __construct()
    {
        $this->phpTemplateExtensions = config('craftile.php_template_extensions', ['craft.php']);
    }

    /**
     * Parse template file (JSON, YAML, or PHP) with automatic caching.
     *
     * @param  string  $path  Path to template file
     * @return array Parsed template data
     *
     * @throws JsonViewException
     */
    public function parse(string $path): array
    {
        // Check in-memory cache first
        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        $data = null;

        // In preview mode, use only in-memory caching
        if (Craftile::inPreview()) {
            $data = $this->parseFile($path);
        } else {
            // In production mode, use Laravel cache
            $cacheKey = 'craftile_template_'.md5($path.'_'.filemtime($path));
            $cacheTtl = config('craftile.cache.ttl', 3600);
            $data = Cache::remember($cacheKey, $cacheTtl, fn () => $this->parseFile($path));
        }

        // Store in in-memory cache
        self::$cache[$path] = $data;

        return $data;
    }

    /**
     * Parse template file without caching.
     *
     * @throws JsonViewException
     */
    protected function parseFile(string $path): array
    {
        if (! file_exists($path)) {
            throw new JsonViewException("Template file not found: {$path}", $path);
        }

        // Check if this is a PHP template
        if ($this->isPhpTemplate($path)) {
            return $this->evaluatePhpTemplate($path);
        }

        // Parse JSON/YAML
        $content = file_get_contents($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'json' => $this->parseJson($content, $path),
            'yml', 'yaml' => $this->parseYaml($content, $path),
            default => throw new JsonViewException("Unsupported template format: {$extension}", $path)
        };
    }

    /**
     * Check if the given path is a PHP template file.
     */
    protected function isPhpTemplate(string $path): bool
    {
        foreach ($this->phpTemplateExtensions as $ext) {
            if (str_ends_with($path, '.'.$ext)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate a PHP template file and return the template data.
     *
     * @throws JsonViewException
     */
    protected function evaluatePhpTemplate(string $path): array
    {
        ob_start();

        try {
            $result = require $path;
            ob_end_clean();

            if (is_array($result)) {
                return $result;
            }

            if ($result instanceof \Craftile\Core\Data\Template) {
                return $result();
            }

            throw new JsonViewException(
                "PHP template must return an array or Template instance: {$path}",
                $path
            );
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new JsonViewException(
                "PHP template evaluation failed: {$path}. {$e->getMessage()}",
                $path,
                0,
                $e
            );
        }
    }

    /**
     * Parse JSON content.
     *
     * @throws JsonViewException
     */
    protected function parseJson(string $content, string $path): array
    {
        try {
            return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new JsonViewException(
                "JSON parsing failed: {$path}. {$e->getMessage()}",
                $path,
                0,
                $e
            );
        }
    }

    /**
     * Parse YAML content.
     *
     * @throws JsonViewException
     */
    protected function parseYaml(string $content, string $path): array
    {
        try {
            return Yaml::parse($content);
        } catch (\Exception $e) {
            throw new JsonViewException(
                "YAML parsing failed: {$path}. {$e->getMessage()}",
                $path,
                0,
                $e
            );
        }
    }

    /**
     * Clear all cached parsed files (useful for testing).
     */
    public function clearCache(): void
    {
        self::$cache = [];
    }
}
