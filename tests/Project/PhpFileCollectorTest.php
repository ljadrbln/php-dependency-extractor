<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Project;

use PhpDependencyExtractor\Project\PhpFileCollector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpFileCollectorTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/PhpFileCollector';

    #[DataProvider('providePaths')]
    public function testCollect(
        string $path,
        array $expectedFiles
    ): void {
        $collector = new PhpFileCollector();

        $actualFiles = $collector->collect($path);

        self::assertSame(
            $expectedFiles,
            $actualFiles
        );
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: list<string>
     * }>
     */
    public static function providePaths(): array
    {
        $root = self::FIXTURES_DIR;

        return [
            'Directory with PHP files' => [
                $root,
                [
                    sprintf(
                        '%s/First.php',
                        $root
                    ),
                    sprintf(
                        '%s/Nested/Second.php',
                        $root
                    ),
                ],
            ],

            'Single PHP file' => [
                sprintf(
                    '%s/First.php',
                    $root
                ),
                [
                    sprintf(
                        '%s/First.php',
                        $root
                    ),
                ],
            ],

            'Single non-PHP file' => [
                sprintf(
                    '%s/readme.txt',
                    $root
                ),
                [],
            ],

            'Missing path' => [
                sprintf(
                    '%s/missing',
                    $root
                ),
                [],
            ],
        ];
    }
}
