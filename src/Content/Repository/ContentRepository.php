<?php

declare(strict_types=1);

namespace App\Content\Repository;

use App\Content\Loader\LocalizedContentLocator;
use App\Content\Loader\YamlContentLoader;
use App\Content\Markdown\MarkdownRenderer;
use App\Content\Model\Experience;
use App\Content\Model\MarkdownContent;
use App\Content\Model\Profile;
use App\Content\Model\Project;
use Psr\Cache\CacheItemPoolInterface;

final readonly class ContentRepository
{
    public function __construct(
        private LocalizedContentLocator $locator,
        private YamlContentLoader $loader,
        private MarkdownRenderer $markdownRenderer,
        private CacheItemPoolInterface $cache,
        private string $environment,
    ) {
    }

    public function profile(string $locale): Profile
    {
        return $this->remember('profile.'.$locale, fn (): Profile => $this->loader->profile($this->locator->profile($locale)));
    }

    public function markdown(string $locale, string $name): MarkdownContent
    {
        return $this->remember(
            'markdown.'.$locale.'.'.$name,
            fn (): MarkdownContent => $this->markdownRenderer->renderFile($this->locator->markdown($locale, $name)),
        );
    }

    /** @return list<Experience> */
    public function experiences(string $locale): array
    {
        /** @var list<Experience> $experiences */
        $experiences = $this->remember('experiences.'.$locale, function () use ($locale): array {
            $items = array_map($this->loader->experience(...), $this->locator->experiences($locale));
            usort($items, static fn (Experience $a, Experience $b): int => $b->period->from <=> $a->period->from);

            return $items;
        });

        return $experiences;
    }

    public function experience(string $locale, string $slug): Experience
    {
        return $this->remember('experience.'.$locale.'.'.$slug, fn (): Experience => $this->loader->experience($this->locator->experience($locale, $slug)));
    }

    /** @return list<Project> */
    public function projects(string $locale): array
    {
        /** @var list<Project> $projects */
        $projects = $this->remember('projects.'.$locale, function () use ($locale): array {
            $items = array_map($this->loader->project(...), $this->locator->projects($locale));
            usort($items, static fn (Project $a, Project $b): int => ($b->period->from ?? '') <=> ($a->period->from ?? ''));

            return $items;
        });

        return $projects;
    }

    public function project(string $locale, string $slug): Project
    {
        return $this->remember('project.'.$locale.'.'.$slug, fn (): Project => $this->loader->project($this->locator->project($locale, $slug)));
    }

    public function hasExperience(string $locale, string $slug): bool
    {
        return $this->locator->existsExperience($locale, $slug);
    }

    public function hasProject(string $locale, string $slug): bool
    {
        return $this->locator->existsProject($locale, $slug);
    }

    /**
     * @template T
     *
     * @param callable(): T $loader
     *
     * @return T
     */
    private function remember(string $key, callable $loader): mixed
    {
        if ($this->environment !== 'prod') {
            return $loader();
        }

        $item = $this->cache->getItem('content.'.hash('xxh128', $key));
        if ($item->isHit()) {
            return $item->get();
        }

        $value = $loader();
        $item->set($value);
        $this->cache->save($item);

        return $value;
    }
}
