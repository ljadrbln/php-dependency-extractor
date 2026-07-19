<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Project;

use PHPUnit\Framework\TestCase;
use PhpDependencyExtractor\Project\Contract\FileDependencyResolverInterface;
use PhpDependencyExtractor\Project\RequiredFileCollector;

final class RequiredFileCollectorTest extends TestCase
{
    public function testCollectReturnsEntryFileWhenThereAreNoDependencies(): void
    {
        $resolver = $this->createMock(
            FileDependencyResolverInterface::class
        );

        $resolver
            ->method('resolve')
            ->willReturn([]);

        $collector = new RequiredFileCollector(
            $resolver
        );

        $files = $collector->collect(
            ['/project/A.php'],
            []
        );

        self::assertSame(
            ['/project/A.php'],
            $files
        );
    }

    public function testCollectCollectsTransitiveDependencies(): void
    {
        $classMap = [
            'App\\B' => '/project/B.php',
            'App\\C' => '/project/C.php',
            'App\\D' => '/project/D.php',
        ];

        $resolver = $this->createMock(
            FileDependencyResolverInterface::class
        );

        $resolver
            ->method('resolve')
            ->willReturnCallback(
                static function (string $file): array {
                    return match ($file) {
                        '/project/A.php' => ['App\\B'],
                        '/project/B.php' => ['App\\C'],
                        '/project/C.php' => ['App\\D'],
                        default => [],
                    };
                }
            );

        $collector = new RequiredFileCollector(
            $resolver
        );

        $files = $collector->collect(
            ['/project/A.php'],
            $classMap
        );

        self::assertSame(
            [
                '/project/A.php',
                '/project/B.php',
                '/project/C.php',
                '/project/D.php',
            ],
            $files
        );
    }

    public function testCollectIgnoresUnknownClasses(): void
    {
        $resolver = $this->createMock(
            FileDependencyResolverInterface::class
        );

        $resolver
            ->method('resolve')
            ->willReturn(
                ['App\\Unknown']
            );

        $collector = new RequiredFileCollector(
            $resolver
        );

        $files = $collector->collect(
            ['/project/A.php'],
            []
        );

        self::assertSame(
            ['/project/A.php'],
            $files
        );
    }

    public function testCollectDoesNotVisitSameFileTwice(): void
    {
        $classMap = [
            'App\\B' => '/project/B.php',
            'App\\C' => '/project/C.php',
            'App\\D' => '/project/D.php',
        ];

        $resolver = $this->createMock(
            FileDependencyResolverInterface::class
        );

        $resolver
            ->method('resolve')
            ->willReturnCallback(
                static function (string $file): array {
                    return match ($file) {
                        '/project/A.php' => [
                            'App\\B',
                            'App\\C',
                        ],
                        '/project/B.php' => [
                            'App\\D',
                        ],
                        '/project/C.php' => [
                            'App\\D',
                        ],
                        default => [],
                    };
                }
            );

        $collector = new RequiredFileCollector(
            $resolver
        );

        $files = $collector->collect(
            ['/project/A.php'],
            $classMap
        );

        self::assertSame(
            [
                '/project/A.php',
                '/project/B.php',
                '/project/C.php',
                '/project/D.php',
            ],
            $files
        );
    }

    public function testCollectHandlesCircularDependencies(): void
    {
        $classMap = [
            'App\\B' => '/project/B.php',
            'App\\C' => '/project/C.php',
            'App\\A' => '/project/A.php',
        ];

        $resolver = $this->createMock(
            FileDependencyResolverInterface::class
        );

        $resolver
            ->method('resolve')
            ->willReturnCallback(
                static function (string $file): array {
                    return match ($file) {
                        '/project/A.php' => ['App\\B'],
                        '/project/B.php' => ['App\\C'],
                        '/project/C.php' => ['App\\A'],
                        default => [],
                    };
                }
            );

        $collector = new RequiredFileCollector(
            $resolver
        );

        $files = $collector->collect(
            ['/project/A.php'],
            $classMap
        );

        self::assertSame(
            [
                '/project/A.php',
                '/project/B.php',
                '/project/C.php',
            ],
            $files
        );
    }

    public function testCollectReturnsSortedFiles(): void
    {
        $classMap = [
            'App\\B' => '/project/B.php',
            'App\\C' => '/project/C.php',
        ];

        $resolver = $this->createMock(
            FileDependencyResolverInterface::class
        );

        $resolver
            ->method('resolve')
            ->willReturnCallback(
                static function (string $file): array {
                    return match ($file) {
                        '/project/A.php' => [
                            'App\\C',
                            'App\\B',
                        ],
                        default => [],
                    };
                }
            );

        $collector = new RequiredFileCollector(
            $resolver
        );

        $files = $collector->collect(
            ['/project/A.php'],
            $classMap
        );

        self::assertSame(
            [
                '/project/A.php',
                '/project/B.php',
                '/project/C.php',
            ],
            $files
        );
    }
}
