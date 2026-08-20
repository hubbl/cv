<?php

declare(strict_types=1);

namespace App\Tests\Integration\Content;

use App\Content\Loader\YamlContentLoader;
use App\Content\Model\ContentException;
use App\Content\Repository\ContentRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class YamlContentLoaderTest extends KernelTestCase
{
    public function testMapsCompleteContentTree(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(ContentRepository::class);
        self::assertInstanceOf(ContentRepository::class, $repository);
        $experience = $repository->experience('de', 'northstar-digital');

        self::assertSame('Northstar Digital GmbH', $experience->company);
        self::assertSame('2022-08', $experience->period->from);
        self::assertNotEmpty($experience->projects);
        self::assertStringContainsString('<strong>', $experience->summary->html ?? '');
    }

    public function testRequiredFieldsAreValidatedEarly(): void
    {
        self::bootKernel();
        $loader = self::getContainer()->get(YamlContentLoader::class);
        self::assertInstanceOf(YamlContentLoader::class, $loader);
        $directory = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'cv-invalid-'.bin2hex(random_bytes(4));
        mkdir($directory);
        $path = $directory.\DIRECTORY_SEPARATOR.'experience.yaml';
        file_put_contents($path, "role: Engineer\nperiod:\n  from: 2024-01\n");

        try {
            $this->expectException(ContentException::class);
            $loader->experience($path);
        } finally {
            @unlink($path);
            @rmdir($directory);
        }
    }
}
