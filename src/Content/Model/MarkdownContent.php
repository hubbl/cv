<?php

declare(strict_types=1);

namespace App\Content\Model;

final readonly class MarkdownContent
{
    public function __construct(
        public string $source,
        public string $html,
    ) {
    }
}
