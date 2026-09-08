<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig\Components;

use App\Content\Loader\LocalizedContentLocator;
use App\Content\Loader\YamlContentLoader;
use App\Content\Markdown\MarkdownRenderer;
use App\Content\Repository\ContentRepository;
use App\Twig\Components\LanguageSwitch;
use League\CommonMark\CommonMarkConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validation;

final class LanguageSwitchTest extends TestCase
{
    public function testMissingTranslationFallsBackToTargetHomepage(): void
    {
        $directory = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'cv-locale-'.bin2hex(random_bytes(4));
        mkdir($directory.\DIRECTORY_SEPARATOR.'de'.\DIRECTORY_SEPARATOR.'projects'.\DIRECTORY_SEPARATOR.'draft', recursive: true);
        mkdir($directory.\DIRECTORY_SEPARATOR.'en', recursive: true);
        touch($directory.\DIRECTORY_SEPARATOR.'de'.\DIRECTORY_SEPARATOR.'projects'.\DIRECTORY_SEPARATOR.'draft'.\DIRECTORY_SEPARATOR.'project.yaml');

        try {
            $locator = new LocalizedContentLocator($directory, ['de', 'en']);
            $sanitizer = $this->createStub(HtmlSanitizerInterface::class);
            $markdown = new MarkdownRenderer(new CommonMarkConverter(), $sanitizer);
            $loader = new YamlContentLoader(
                $locator,
                $markdown,
                Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
            );
            $repository = new ContentRepository($locator, $loader, $markdown, new ArrayAdapter(), 'test');

            $request = Request::create('/de/projects/draft');
            $request->setLocale('de');
            $request->attributes->set('_route', 'app_project_show');
            $request->attributes->set('slug', 'draft');
            $stack = new RequestStack();
            $stack->push($request);

            $router = $this->createMock(UrlGeneratorInterface::class);
            $router->expects(self::once())->method('generate')
                ->with('app_home', ['_locale' => 'en'])
                ->willReturn('/en/');

            self::assertSame('/en/', (new LanguageSwitch($stack, $router, $repository))->getUrl());
        } finally {
            @unlink($directory.\DIRECTORY_SEPARATOR.'de'.\DIRECTORY_SEPARATOR.'projects'.\DIRECTORY_SEPARATOR.'draft'.\DIRECTORY_SEPARATOR.'project.yaml');
            @rmdir($directory.\DIRECTORY_SEPARATOR.'de'.\DIRECTORY_SEPARATOR.'projects'.\DIRECTORY_SEPARATOR.'draft');
            @rmdir($directory.\DIRECTORY_SEPARATOR.'de'.\DIRECTORY_SEPARATOR.'projects');
            @rmdir($directory.\DIRECTORY_SEPARATOR.'de');
            @rmdir($directory.\DIRECTORY_SEPARATOR.'en');
            @rmdir($directory);
        }
    }
}
