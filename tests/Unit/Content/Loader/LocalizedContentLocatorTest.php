<?php

declare(strict_types=1);

namespace App\Tests\Unit\Content\Loader;

use App\Content\Loader\LocalizedContentLocator;
use App\Content\Model\ContentException;
use PHPUnit\Framework\TestCase;

final class LocalizedContentLocatorTest extends TestCase
{
    public function testLocatesMirroredContentAndRejectsTraversal(): void
    {
        $locator = new LocalizedContentLocator(\dirname(__DIR__, 4).'/content', ['de', 'en']);
        self::assertStringEndsWith('de'.\DIRECTORY_SEPARATOR.'profile.yaml', $locator->profile('de'));
        self::assertTrue($locator->existsExperience('en', 'smartbroker'));

        $this->expectException(ContentException::class);
        $locator->relativeMarkdown($locator->profile('de'), '../secret.md');
    }

    public function testUnsupportedLocaleIsRejected(): void
    {
        $locator = new LocalizedContentLocator(\dirname(__DIR__, 4).'/content', ['de', 'en']);
        $this->expectException(ContentException::class);
        $locator->profile('fr');
    }
}
