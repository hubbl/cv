<?php

declare(strict_types=1);

namespace App\Controller;

use App\Content\Repository\ContentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}', requirements: ['_locale' => 'de|en'])]
final class LegalController extends AbstractController
{
    public function __construct(private readonly ContentRepository $content)
    {
    }

    #[Route('/impressum', name: 'app_imprint', methods: ['GET'])]
    public function imprint(string $_locale): Response
    {
        return $this->render('portfolio/imprint.html.twig', [
            'content' => $this->content->markdown($_locale, 'imprint'),
        ]);
    }

    #[Route('/datenschutz', name: 'app_privacy', methods: ['GET'])]
    public function privacy(string $_locale): Response
    {
        return $this->render('portfolio/privacy.html.twig', [
            'content' => $this->content->markdown($_locale, 'privacy'),
        ]);
    }
}
