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
        self::assertSelectorTextContains('h1', 'Anwendungsentwicklung');
        self::assertSelectorTextContains('body', 'Ausbildung zum IT-Assistenten');
        self::assertSelectorExists('a[href="mailto:webmaster@dennis-otto.net"]');
        self::assertSelectorExists('a[href="tel:+4917662247871"]');
        self::assertSelectorExists('a[href*="Lebenslauf_Dennis_Otto_DE"]');
        self::assertSelectorExists('a[hreflang="en"][href="/en/"]');
        self::assertSelectorExists('meta[property="og:title"][content*="Dennis Otto"]');
        self::assertSelectorExists('meta[property="og:image"][content="http://localhost:8080/og.png"]');

        $client->request('GET', '/en/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Full Stack, APIs, Data Transformation');
        self::assertSelectorTextContains('body', 'Education and languages');
        self::assertSelectorExists('a[href*="CV_Dennis_Otto_EN"]');
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

    public function testLegalPagesAreLinkedAndLocalized(): void
    {
        $client = static::createClient();
        $client->request('GET', '/de/');
        self::assertSelectorExists('footer a[href="/de/impressum"]');
        self::assertSelectorExists('footer a[href="/de/datenschutz"]');

        $client->request('GET', '/de/impressum');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Impressum');
        self::assertSelectorExists('a[hreflang="en"][href="/en/impressum"]');

        $client->request('GET', '/en/datenschutz');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Privacy Policy');
        self::assertSelectorExists('a[hreflang="de"][href="/de/datenschutz"]');
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
