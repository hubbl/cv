<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Content\Model\Technology;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class TechnologyBadge
{
    public Technology $technology;
}
