<?php

declare(strict_types=1);

namespace App\Content\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Profile
{
    /**
     * @param list<Technology> $technologies
     * @param list<Link>       $links
     * @param list<string>     $featuredExperiences
     * @param list<string>     $featuredProjects
     */
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public string $title,
        #[Assert\NotBlank]
        public string $tagline,
        public ?string $location,
        #[Assert\Valid]
        public ?MarkdownContent $introduction,
        #[Assert\Valid]
        public array $technologies,
        #[Assert\Valid]
        public array $links,
        public array $featuredExperiences,
        public array $featuredProjects,
    ) {
    }
}
