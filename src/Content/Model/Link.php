<?php

declare(strict_types=1);

namespace App\Content\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Link
{
    public function __construct(
        #[Assert\NotBlank]
        public string $label,
        #[Assert\Url(protocols: ['https'])]
        public string $url,
    ) {
    }
}
