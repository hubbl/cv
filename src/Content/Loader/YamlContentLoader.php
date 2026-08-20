<?php

declare(strict_types=1);

namespace App\Content\Loader;

use App\Content\Markdown\MarkdownRenderer;
use App\Content\Model\ContentException;
use App\Content\Model\Experience;
use App\Content\Model\ExperienceProject;
use App\Content\Model\Link;
use App\Content\Model\MarkdownContent;
use App\Content\Model\Period;
use App\Content\Model\Profile;
use App\Content\Model\Project;
use App\Content\Model\Technology;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final readonly class YamlContentLoader
{
    public function __construct(
        private LocalizedContentLocator $locator,
        private MarkdownRenderer $markdown,
        private ValidatorInterface $validator,
    ) {
    }

    public function profile(string $path): Profile
    {
        $data = $this->parse($path);
        $profile = new Profile(
            $this->string($data, 'name'),
            $this->string($data, 'title'),
            $this->string($data, 'tagline'),
            $this->nullableString($data, 'location'),
            $this->markdown($path, $data, 'introduction'),
            $this->technologies($data['technologies'] ?? []),
            $this->links($data['links'] ?? []),
            $this->stringList($data['featured_experiences'] ?? [], 'featured_experiences'),
            $this->stringList($data['featured_projects'] ?? [], 'featured_projects'),
        );

        return $this->validated($profile, $path);
    }

    public function experience(string $path): Experience
    {
        $data = $this->parse($path);
        $projects = [];
        foreach ($this->array($data['projects'] ?? [], 'projects') as $index => $project) {
            if (!\is_array($project)) {
                throw new ContentException(\sprintf('%s: projects.%s must be a map.', $path, $index));
            }
            $projects[] = new ExperienceProject(
                $this->string($project, 'title'),
                $this->markdown($path, $project, 'description'),
                $this->technologies($project['technologies'] ?? []),
            );
        }

        $experience = new Experience(
            basename(\dirname($path)),
            $this->string($data, 'company'),
            $this->string($data, 'role'),
            $this->period($data['period'] ?? null),
            $this->nullableString($data, 'location'),
            $this->markdown($path, $data, 'summary'),
            $this->stringList($data['responsibilities'] ?? [], 'responsibilities'),
            $this->stringList($data['highlights'] ?? [], 'highlights'),
            $projects,
            $this->technologies($data['technologies'] ?? []),
            $this->links($data['links'] ?? []),
        );

        return $this->validated($experience, $path);
    }

    public function project(string $path): Project
    {
        $data = $this->parse($path);
        $project = new Project(
            basename(\dirname($path)),
            $this->string($data, 'title'),
            $this->string($data, 'summary'),
            isset($data['period']) ? $this->period($data['period']) : null,
            $this->markdown($path, $data, 'description'),
            $this->markdown($path, $data, 'motivation'),
            $this->markdown($path, $data, 'architecture'),
            $this->stringList($data['highlights'] ?? [], 'highlights'),
            $this->technologies($data['technologies'] ?? []),
            $this->links($data['links'] ?? []),
        );

        return $this->validated($project, $path);
    }

    /** @return array<string, mixed> */
    private function parse(string $path): array
    {
        try {
            $data = Yaml::parseFile($path, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
        } catch (ParseException $exception) {
            throw new ContentException(\sprintf('Invalid YAML in "%s": %s', $path, $exception->getMessage()), previous: $exception);
        }

        if (!\is_array($data)) {
            throw new ContentException(\sprintf('The root of "%s" must be a map.', $path));
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (!\is_string($value) || '' === trim($value)) {
            throw new ContentException(\sprintf('Field "%s" must be a non-empty string.', $key));
        }

        return trim($value);
    }

    /** @param array<string, mixed> $data */
    private function nullableString(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        return $this->string($data, $key);
    }

    private function period(mixed $value): Period
    {
        if (!\is_array($value)) {
            throw new ContentException('Field "period" must be a map.');
        }

        return new Period($this->string($value, 'from'), $this->nullableString($value, 'to'));
    }

    /** @param array<string, mixed> $data */
    private function markdown(string $yamlPath, array $data, string $key): ?MarkdownContent
    {
        if (!isset($data[$key])) {
            return null;
        }

        $relative = $this->string($data, $key);

        return $this->markdown->renderFile($this->locator->relativeMarkdown($yamlPath, $relative));
    }

    /** @return list<Technology> */
    private function technologies(mixed $value): array
    {
        return array_map(static fn (string $name): Technology => new Technology($name), $this->stringList($value, 'technologies'));
    }

    /** @return list<Link> */
    private function links(mixed $value): array
    {
        $links = [];
        foreach ($this->array($value, 'links') as $index => $link) {
            if (!\is_array($link)) {
                throw new ContentException(\sprintf('links.%s must be a map.', $index));
            }
            $links[] = new Link($this->string($link, 'label'), $this->string($link, 'url'));
        }

        return $links;
    }

    /** @return list<string> */
    private function stringList(mixed $value, string $field): array
    {
        $values = $this->array($value, $field);
        foreach ($values as $index => $item) {
            if (!\is_string($item) || '' === trim($item)) {
                throw new ContentException(\sprintf('%s.%s must be a non-empty string.', $field, $index));
            }
            $values[$index] = trim($item);
        }

        return $values;
    }

    /** @return list<mixed> */
    private function array(mixed $value, string $field): array
    {
        if (!\is_array($value) || !array_is_list($value)) {
            throw new ContentException(\sprintf('Field "%s" must be a list.', $field));
        }

        return $value;
    }

    /**
     * @template T of object
     *
     * @param T $content
     *
     * @return T
     */
    private function validated(object $content, string $path): object
    {
        $violations = $this->validator->validate($content);
        if (0 !== \count($violations)) {
            throw new ContentException(\sprintf('Invalid content in "%s": %s', $path, (string) $violations));
        }

        return $content;
    }
}
