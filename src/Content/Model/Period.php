<?php

declare(strict_types=1);

namespace App\Content\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Period
{
    private const FORMAT = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public function __construct(
        #[Assert\Regex(self::FORMAT)]
        public string $from,
        #[Assert\Regex(self::FORMAT)]
        public ?string $to = null,
    ) {
        if (!preg_match(self::FORMAT, $from) || (null !== $to && !preg_match(self::FORMAT, $to))) {
            throw new \InvalidArgumentException('Periods must use YYYY-MM values.');
        }

        if (null !== $to && $to < $from) {
            throw new \InvalidArgumentException('The period end cannot precede its start.');
        }
    }
}
