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
        self::assertSelectorTextContains('h1', 'Web-/Anwendungsentwicklung');
        self::assertSelectorExists('a[hreflang="en"][href="/en/"]');
        self::assertSelectorExists('meta[property="og:title"][content*="Dennis Otto"]');
        self::assertSelectorExists('meta[property="og:image"][content="http://localhost:8080/og.png"]');

        $client->request('GET', '/en/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Backend, integration, and data');
    }

    public function testExperiencePagesAndEquivalentLanguageSwitch(): void
    {
        $client = static::createClient();
        foreach (['/de/experience', '/en/experience'] as $url) {
            $client->request('GET', $url);
            self::assertResponseIsSuccessful();
        }

        $client->request('GET', '/de/experience/smartbroker');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Senior Backend-Entwickler');
        self::assertSelectorTextContains('body', 'Depot-Registrierung');
        self::assertSelectorExists('a[hreflang="en"][href="/en/experience/smartbroker"]');
    }

    public function testUnknownSlugMissingTranslationAndLocaleReturnNotFound(): void
    {
        $client = static::createClient();
        foreach (['/de/experience/unknown-company', '/en/projects/not-translated', '/fr/'] as $url) {
            $client->request('GET', $url);
            self::assertResponseStatusCodeSame(404);
        }
    }

    public function testNoStandaloneProjectsRemain(): void
    {
        $client = static::createClient();
        $client->request('GET', '/de/projects');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('article');
        $client->request('GET', '/de/projects/nonexistent-project');
        self::assertResponseStatusCodeSame(404);
    }
}
