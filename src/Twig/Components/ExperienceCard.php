<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Content\Model\Experience;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ExperienceCard
{
    public Experience $experience;
}
