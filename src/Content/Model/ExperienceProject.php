<?php

declare(strict_types=1);

namespace App\Content\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ExperienceProject
{
    /** @param list<Technology> $technologies */
    public function __construct(
        #[Assert\NotBlank]
        public string $title,
        #[Assert\Valid]
        public ?MarkdownContent $description,
        #[Assert\Valid]
        public array $technologies,
    ) {
    }
}
