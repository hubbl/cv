<?php

declare(strict_types=1);

namespace App\Tests\Unit\Content\Model;

use App\Content\Model\Period;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PeriodTest extends TestCase
{
    public function testOpenPeriodIsValid(): void
    {
        $period = new Period('2024-01');
        self::assertSame('2024-01', $period->from);
        self::assertNull($period->to);
    }

    #[DataProvider('invalidPeriods')]
    public function testInvalidPeriodIsRejected(string $from, ?string $to): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Period($from, $to);
    }

    /** @return iterable<string, array{string, ?string}> */
    public static function invalidPeriods(): iterable
    {
        yield 'invalid month' => ['2024-13', null];
        yield 'missing month' => ['2024', null];
        yield 'reversed' => ['2024-06', '2024-05'];
    }
}
