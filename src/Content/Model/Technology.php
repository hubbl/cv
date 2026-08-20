<?php

declare(strict_types=1);

namespace App\Content\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Technology
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
    ) {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('A technology name cannot be empty.');
        }
    }
}
