<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel K
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Project;

use PhpDependencyExtractor\Parser\NamespaceReader;
use PhpDependencyExtractor\Project\ClassMapBuilder;
use PhpDependencyExtractor\Project\DeclaredClassReader;
use PhpDependencyExtractor\Project\PhpFileCollector;
use PhpDependencyExtractor\Project\PhpFileReader;
use PHPUnit\Framework\TestCase;

final class ClassMapBuilderTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ClassMapBuilder';

    public function testBuild(): void
    {
        $phpFileCollector = new PhpFileCollector();

        $phpFileReader = new PhpFileReader();

        $namespaceReader = new NamespaceReader();

        $declaredClassReader = new DeclaredClassReader(
            $namespaceReader
        );

        $builder = new ClassMapBuilder(
            $phpFileCollector,
            $phpFileReader,
            $declaredClassReader
        );

        $actualClassMap = $builder->build(
            self::FIXTURES_DIR
        );

        $expectedClassMap = [
            'App\\Infrastructure\\Database' => sprintf(
                '%s/Infrastructure/Database.php',
                self::FIXTURES_DIR
            ),
            'App\\User\\User' => sprintf(
                '%s/User/User.php',
                self::FIXTURES_DIR
            ),
            'App\\User\\UserRepositoryInterface' => sprintf(
                '%s/User/UserRepositoryInterface.php',
                self::FIXTURES_DIR
            ),
        ];

        self::assertSame(
            $expectedClassMap,
            $actualClassMap
        );
    }
}
