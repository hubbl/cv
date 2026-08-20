<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Content\Repository\ContentRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final readonly class LanguageSwitch
{
    public function __construct(
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private ContentRepository $content,
    ) {
    }

    public function getTargetLocale(): string
    {
        return 'de' === $this->requestStack->getCurrentRequest()?->getLocale() ? 'en' : 'de';
    }

    public function getUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $this->getTargetLocale();
        $route = (string) $request?->attributes->get('_route', 'app_home');
        $parameters = ['_locale' => $locale];
        $slug = $request?->attributes->get('slug');

        if (\is_string($slug) && 'app_experience_show' === $route && $this->content->hasExperience($locale, $slug)) {
            $parameters['slug'] = $slug;
        } elseif (\is_string($slug) && 'app_project_show' === $route && $this->content->hasProject($locale, $slug)) {
            $parameters['slug'] = $slug;
        } elseif (\in_array($route, ['app_experience_show', 'app_project_show'], true)) {
            $route = 'app_home';
        }

        return $this->urlGenerator->generate($route, $parameters);
    }
}
