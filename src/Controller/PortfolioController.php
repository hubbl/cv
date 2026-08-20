<?php

declare(strict_types=1);

namespace App\Controller;

use App\Content\Model\ContentNotFound;
use App\Content\Repository\ContentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}', requirements: ['_locale' => 'de|en'])]
final class PortfolioController extends AbstractController
{
    public function __construct(private readonly ContentRepository $content)
    {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(string $_locale): Response
    {
        $profile = $this->content->profile($_locale);
        $experiences = array_filter(
            $this->content->experiences($_locale),
            static fn ($item): bool => \in_array($item->slug, $profile->featuredExperiences, true),
        );
        $projects = array_filter(
            $this->content->projects($_locale),
            static fn ($item): bool => \in_array($item->slug, $profile->featuredProjects, true),
        );

        return $this->render('portfolio/home.html.twig', [
            'profile' => $profile,
            'experiences' => $experiences,
            'projects' => $projects,
        ]);
    }

    #[Route('/experience', name: 'app_experience_index', methods: ['GET'])]
    public function experienceIndex(string $_locale): Response
    {
        return $this->render('portfolio/experience/index.html.twig', [
            'experiences' => $this->content->experiences($_locale),
        ]);
    }

    #[Route('/experience/{slug}', name: 'app_experience_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function experienceShow(string $_locale, string $slug): Response
    {
        try {
            $experience = $this->content->experience($_locale, $slug);
        } catch (ContentNotFound $exception) {
            throw $this->createNotFoundException('Experience not found.', $exception);
        }

        return $this->render('portfolio/experience/show.html.twig', ['experience' => $experience]);
    }

    #[Route('/projects', name: 'app_project_index', methods: ['GET'])]
    public function projectIndex(string $_locale): Response
    {
        return $this->render('portfolio/project/index.html.twig', [
            'projects' => $this->content->projects($_locale),
        ]);
    }

    #[Route('/projects/{slug}', name: 'app_project_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function projectShow(string $_locale, string $slug): Response
    {
        try {
            $project = $this->content->project($_locale, $slug);
        } catch (ContentNotFound $exception) {
            throw $this->createNotFoundException('Project not found.', $exception);
        }

        return $this->render('portfolio/project/show.html.twig', ['project' => $project]);
    }
}
