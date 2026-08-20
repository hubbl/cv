<?php

declare(strict_types=1);

namespace App\Content\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Project
{
    /**
     * @param list<string>     $highlights
     * @param list<Technology> $technologies
     * @param list<Link>       $links
     */
    public function __construct(
        #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public string $slug,
        #[Assert\NotBlank]
        public string $title,
        #[Assert\NotBlank]
        public string $summary,
        #[Assert\Valid]
        public ?Period $period,
        #[Assert\Valid]
        public ?MarkdownContent $description,
        #[Assert\Valid]
        public ?MarkdownContent $motivation,
        #[Assert\Valid]
        public ?MarkdownContent $architecture,
        #[Assert\All([new Assert\NotBlank()])]
        public array $highlights,
        #[Assert\Valid]
        public array $technologies,
        #[Assert\Valid]
        public array $links,
    ) {
    }
}
