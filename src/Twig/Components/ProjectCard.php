<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Content\Model\Project;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ProjectCard
{
    public Project $project;
}
