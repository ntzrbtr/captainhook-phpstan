<?php

declare(strict_types=1);

namespace Netzarbeiter\CaptainHookPhpstan;

/**
 * Decides whether a file is covered by PHPStan's `paths` and not hit by `excludePaths`.
 *
 * All paths are expected to be absolute, as returned by `phpstan dump-parameters`.
 */
final class PathFilter
{
    /** @var string[] */
    private array $paths;

    /** @var string[] */
    private array $excludePaths;

    /**
     * @param string[] $paths
     * @param string[] $excludePaths
     */
    public function __construct(array $paths, array $excludePaths)
    {
        $this->paths        = $paths;
        $this->excludePaths = $excludePaths;
    }

    /**
     * Builds a filter from the decoded output of `phpstan dump-parameters --json`.
     *
     * @param array<mixed> $parameters
     */
    public static function fromParameters(array $parameters): self
    {
        return new self(
            self::collectPaths($parameters['paths'] ?? []),
            // `excludePaths` is either a flat list or split into `analyse` / `analyseAndScan`.
            self::collectPaths($parameters['excludePaths'] ?? [])
        );
    }

    /**
     * @param  mixed $value
     * @return string[]
     */
    private static function collectPaths($value): array
    {
        if (is_string($value)) {
            // Resolve symlinks so configured paths compare equal to the (resolved) staged files.
            $real = realpath($value);

            return [$real !== false ? $real : $value];
        }

        $paths = [];
        foreach (is_array($value) ? $value : [] as $item) {
            $paths = array_merge($paths, self::collectPaths($item));
        }

        return $paths;
    }

    /**
     * @param  string[] $files Absolute file paths
     * @return string[]
     */
    public function filter(array $files): array
    {
        return array_values(array_filter($files, [$this, 'accepts']));
    }

    public function accepts(string $file): bool
    {
        return $this->matchesAny($file, $this->paths) && !$this->matchesAny($file, $this->excludePaths);
    }

    /**
     * @param string[] $patterns
     */
    private function matchesAny(string $file, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (self::matches($file, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private static function matches(string $file, string $pattern): bool
    {
        $pattern = rtrim($pattern, '/');

        if ($file === $pattern || strpos($file, $pattern . '/') === 0) {
            return true;
        }

        // Same semantics PHPStan uses for wildcard excludes.
        return strpbrk($pattern, '*?[') !== false && fnmatch($pattern, $file);
    }
}
