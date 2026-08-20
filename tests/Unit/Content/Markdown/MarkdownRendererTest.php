<?php

declare(strict_types=1);

namespace App\Tests\Unit\Content\Markdown;

use App\Content\Markdown\MarkdownRenderer;
use League\CommonMark\CommonMarkConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

final class MarkdownRendererTest extends TestCase
{
    public function testMarkdownIsRenderedAndSanitized(): void
    {
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::once())->method('sanitize')
            ->with(self::stringContains('<strong>safe</strong>'))
            ->willReturn('<p><strong>safe</strong></p>');

        $path = tempnam(sys_get_temp_dir(), 'markdown-');
        self::assertIsString($path);
        file_put_contents($path, '**safe** <script>alert(1)</script>');

        try {
            $content = (new MarkdownRenderer(new CommonMarkConverter(['html_input' => 'strip']), $sanitizer))->renderFile($path);
            self::assertSame('<p><strong>safe</strong></p>', $content->html);
        } finally {
            unlink($path);
        }
    }
}
