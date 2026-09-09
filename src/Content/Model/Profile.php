<?php

declare(strict_types=1);

namespace App\Content\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Profile
{
    /**
     * @param list<Technology>                                          $technologies
     * @param list<Link>                                                $links
     * @param list<array{title: string, detail: string, meta: ?string}> $education
     * @param list<array{title: string, detail: string, meta: ?string}> $languages
     * @param list<array{title: string, detail: string, meta: ?string}> $certifications
     * @param list<string>                                              $featuredExperiences
     * @param list<string>                                              $featuredProjects
     */
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public string $title,
        #[Assert\NotBlank]
        public string $tagline,
        public ?string $location,
        public ?string $email,
        public ?string $phone,
        public ?string $resume,
        public string $introductionTitle,
        #[Assert\Valid]
        public ?MarkdownContent $introduction,
        #[Assert\Valid]
        public array $technologies,
        #[Assert\Valid]
        public array $links,
        #[Assert\Valid]
        public array $education,
        #[Assert\Valid]
        public array $languages,
        #[Assert\Valid]
        public array $certifications,
        public array $featuredExperiences,
        public array $featuredProjects,
    ) {
    }
}
