<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PortfolioTest extends WebTestCase
{
    public function testGermanAndEnglishHomepages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/de/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Software Engineer');
        self::assertSelectorExists('a[hreflang="en"][href="/en/"]');
        self::assertSelectorExists('meta[property="og:title"][content*="Software Engineer"]');
        self::assertSelectorExists('meta[property="og:image"][content="http://localhost:8080/og.png"]');

        $client->request('GET', '/en/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Technology with a clear purpose');
    }

    public function testExperiencePagesAndEquivalentLanguageSwitch(): void
    {
        $client = static::createClient();
        foreach (['/de/experience', '/en/experience'] as $url) {
            $client->request('GET', $url);
            self::assertResponseIsSuccessful();
        }

        $client->request('GET', '/de/experience/northstar-digital');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Senior Software Engineer');
        self::assertSelectorExists('a[hreflang="en"][href="/en/experience/northstar-digital"]');
    }

    public function testUnknownSlugMissingTranslationAndLocaleReturnNotFound(): void
    {
        $client = static::createClient();
        foreach (['/de/experience/unknown-company', '/en/projects/not-translated', '/fr/'] as $url) {
            $client->request('GET', $url);
            self::assertResponseStatusCodeSame(404);
        }
    }

    public function testProjectPages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/de/projects');
        self::assertResponseIsSuccessful();
        $client->clickLink('Content-first CV');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Architektur');
        self::assertSelectorExists('meta[property="og:title"][content*="Content-first CV"]');
        self::assertSelectorNotExists('meta[property="og:image"]');
    }
}
