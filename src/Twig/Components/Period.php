<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Content\Model\Period as PeriodValue;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Period
{
    public PeriodValue $period;
}
