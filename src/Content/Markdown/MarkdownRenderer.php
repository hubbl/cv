<?php

declare(strict_types=1);

namespace App\Content\Markdown;

use App\Content\Model\ContentException;
use App\Content\Model\MarkdownContent;
use League\CommonMark\ConverterInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

final readonly class MarkdownRenderer
{
    public function __construct(
        private ConverterInterface $converter,
        private HtmlSanitizerInterface $sanitizer,
    ) {
    }

    public function renderFile(string $path): MarkdownContent
    {
        $source = file_get_contents($path);
        if (false === $source) {
            throw new ContentException(\sprintf('Could not read Markdown file "%s".', $path));
        }

        $html = $this->converter->convert($source)->getContent();

        return new MarkdownContent($source, $this->sanitizer->sanitize($html));
    }
}
