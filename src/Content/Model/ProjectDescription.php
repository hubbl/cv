<?php

declare(strict_types=1);

namespace App\Content\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectDescription
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
    ) {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('A description name cannot be empty.');
        }
    }
}
