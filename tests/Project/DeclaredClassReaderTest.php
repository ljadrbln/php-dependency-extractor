<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Project;

use PhpDependencyExtractor\Parser\NamespaceReader;
use PhpDependencyExtractor\Project\DeclaredClassReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeclaredClassReaderTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/DeclaredClassReader';

    #[DataProvider('provideDeclaredClasses')]
    public function testRead(
        string $fixtureFile,
        ?string $expectedClassName
    ): void {
        $fixturePath = sprintf(
            '%s/%s',
            self::FIXTURES_DIR,
            $fixtureFile
        );

        self::assertFileExists($fixturePath);

        $phpCode = file_get_contents($fixturePath);

        self::assertNotFalse($phpCode);

        $namespaceReader = new NamespaceReader();

        $reader = new DeclaredClassReader(
            $namespaceReader
        );

        $actualClassName = $reader->read($phpCode);

        self::assertSame(
            $expectedClassName,
            $actualClassName
        );
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: string|null
     * }>
     */
    public static function provideDeclaredClasses(): array
    {
        return [
            'Class with namespace' => [
                'class-with-namespace.php.txt',
                'App\User\UserService',
            ],

            'Class without namespace' => [
                'class-without-namespace.php.txt',
                'UserService',
            ],

            'Interface declaration' => [
                'interface.php.txt',
                'App\User\UserRepositoryInterface',
            ],

            'Trait declaration' => [
                'trait.php.txt',
                'App\User\Timestampable',
            ],

            'Enum declaration' => [
                'enum.php.txt',
                'App\User\UserStatus',
            ],

            'Anonymous class before named class' => [
                'anonymous-class.php.txt',
                'App\User\UserService',
            ],

            'No declaration' => [
                'no-declaration.php.txt',
                null,
            ],

            'Empty source' => [
                'empty.php.txt',
                null,
            ],
        ];
    }
}
