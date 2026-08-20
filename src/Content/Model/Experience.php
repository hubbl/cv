<?php

declare(strict_types=1);

namespace App\Content\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Experience
{
    /**
     * @param list<string>            $responsibilities
     * @param list<string>            $highlights
     * @param list<ExperienceProject> $projects
     * @param list<Technology>        $technologies
     * @param list<Link>              $links
     */
    public function __construct(
        #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public string $slug,
        #[Assert\NotBlank]
        public string $company,
        #[Assert\NotBlank]
        public string $role,
        #[Assert\Valid]
        public Period $period,
        public ?string $location,
        #[Assert\Valid]
        public ?MarkdownContent $summary,
        #[Assert\All([new Assert\NotBlank()])]
        public array $responsibilities,
        #[Assert\All([new Assert\NotBlank()])]
        public array $highlights,
        #[Assert\Valid]
        public array $projects,
        #[Assert\Valid]
        public array $technologies,
        #[Assert\Valid]
        public array $links,
    ) {
    }
}
