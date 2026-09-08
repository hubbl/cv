<?php

declare(strict_types=1);

namespace App\Content\Loader;

use App\Content\Model\ContentException;
use App\Content\Model\ContentNotFound;

final readonly class LocalizedContentLocator
{
    /** @param list<string> $supportedLocales */
    public function __construct(
        private string $contentDirectory,
        private array $supportedLocales,
    ) {
    }

    public function profile(string $locale): string
    {
        return $this->existingPath($locale, 'profile.yaml');
    }

    public function markdown(string $locale, string $name): string
    {
        $this->assertSlug($name);

        return $this->existingPath($locale, $name.'.md');
    }

    public function experience(string $locale, string $slug): string
    {
        $this->assertSlug($slug);

        return $this->existingPath($locale, \sprintf('experience/%s/experience.yaml', $slug));
    }

    public function project(string $locale, string $slug): string
    {
        $this->assertSlug($slug);

        return $this->existingPath($locale, \sprintf('projects/%s/project.yaml', $slug));
    }

    /** @return list<string> */
    public function experiences(string $locale): array
    {
        return $this->glob($locale, 'experience/*/experience.yaml');
    }

    /** @return list<string> */
    public function projects(string $locale): array
    {
        return $this->glob($locale, 'projects/*/project.yaml');
    }

    public function relativeMarkdown(string $yamlPath, string $relativePath): string
    {
        if (str_contains($relativePath, '..') || str_starts_with($relativePath, '/') || !str_ends_with($relativePath, '.md')) {
            throw new ContentException(\sprintf('Unsafe Markdown path "%s".', $relativePath));
        }

        $path = \dirname($yamlPath).\DIRECTORY_SEPARATOR.str_replace('/', \DIRECTORY_SEPARATOR, $relativePath);
        if (!is_file($path)) {
            throw new ContentNotFound(\sprintf('Markdown file "%s" does not exist.', $relativePath));
        }

        return $path;
    }

    public function existsExperience(string $locale, string $slug): bool
    {
        return $this->exists($locale, \sprintf('experience/%s/experience.yaml', $slug));
    }

    public function existsProject(string $locale, string $slug): bool
    {
        return $this->exists($locale, \sprintf('projects/%s/project.yaml', $slug));
    }

    private function existingPath(string $locale, string $relative): string
    {
        $path = $this->path($locale, $relative);
        if (!is_file($path)) {
            throw new ContentNotFound(\sprintf('Content "%s/%s" does not exist.', $locale, $relative));
        }

        return $path;
    }

    /** @return list<string> */
    private function glob(string $locale, string $pattern): array
    {
        $root = $this->path($locale, '');
        $files = glob($root.str_replace('/', \DIRECTORY_SEPARATOR, $pattern)) ?: [];
        sort($files);

        return $files;
    }

    private function exists(string $locale, string $relative): bool
    {
        try {
            return is_file($this->path($locale, $relative));
        } catch (ContentException) {
            return false;
        }
    }

    private function path(string $locale, string $relative): string
    {
        if (!\in_array($locale, $this->supportedLocales, true)) {
            throw new ContentException(\sprintf('Unsupported locale "%s".', $locale));
        }

        return rtrim($this->contentDirectory, '/\\').\DIRECTORY_SEPARATOR.$locale.\DIRECTORY_SEPARATOR.str_replace('/', \DIRECTORY_SEPARATOR, $relative);
    }

    private function assertSlug(string $slug): void
    {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new ContentNotFound('Invalid content slug.');
        }
    }
}
