<?php

declare(strict_types=1);

namespace Netzarbeiter\CaptainHookPhpstan\Tests;

use Netzarbeiter\CaptainHookPhpstan\PathFilter;
use PHPUnit\Framework\TestCase;

final class PathFilterTest extends TestCase
{
    public function testAcceptsFilesInsideConfiguredDirectories(): void
    {
        $filter = new PathFilter(['/project/app', '/project/database/'], []);

        self::assertTrue($filter->accepts('/project/app/Models/Customer.php'));
        self::assertTrue($filter->accepts('/project/database/seeders/Seeder.php'));
    }

    public function testRejectsFilesOutsideConfiguredPaths(): void
    {
        $filter = new PathFilter(['/project/app'], []);

        self::assertFalse($filter->accepts('/project/routes/web.php'));
        self::assertFalse($filter->accepts('/project/application/Foo.php'));
    }

    public function testAcceptsExplicitlyConfiguredFile(): void
    {
        $filter = new PathFilter(['/project/app/helpers.php'], []);

        self::assertTrue($filter->accepts('/project/app/helpers.php'));
        self::assertFalse($filter->accepts('/project/app/Other.php'));
    }

    public function testRejectsExcludedDirectoriesFilesAndWildcards(): void
    {
        $filter = new PathFilter(
            ['/project/app'],
            ['/project/app/Legacy', '/project/app/helpers.php', '*/Generated/*']
        );

        self::assertFalse($filter->accepts('/project/app/Legacy/Old.php'));
        self::assertFalse($filter->accepts('/project/app/helpers.php'));
        self::assertFalse($filter->accepts('/project/app/Generated/Proxy.php'));
        self::assertTrue($filter->accepts('/project/app/LegacyBridge.php'));
    }

    public function testBuildsFromSplitExcludePaths(): void
    {
        $filter = PathFilter::fromParameters([
            'paths'        => ['/project/app'],
            'excludePaths' => [
                'analyse'        => ['/project/app/A'],
                'analyseAndScan' => ['/project/app/B'],
            ],
        ]);

        self::assertSame(
            ['/project/app/C/Foo.php'],
            $filter->filter(['/project/app/A/Foo.php', '/project/app/B/Foo.php', '/project/app/C/Foo.php'])
        );
    }

    public function testBuildsFromFlatExcludePaths(): void
    {
        $filter = PathFilter::fromParameters([
            'paths'        => ['/project/app'],
            'excludePaths' => ['/project/app/A'],
        ]);

        self::assertFalse($filter->accepts('/project/app/A/Foo.php'));
    }
}
