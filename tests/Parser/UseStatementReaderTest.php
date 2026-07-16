<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Parser;

use PhpDependencyExtractor\Parser\UseStatementReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UseStatementReaderTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/UseStatementReader';

    #[DataProvider('provideUseStatements')]
    public function testRead(
        string $fixtureFile,
        array $expectedImports
    ): void {
        $fixturePath = sprintf(
            '%s/%s',
            self::FIXTURES_DIR,
            $fixtureFile
        );

        self::assertFileExists($fixturePath);

        $phpCode = file_get_contents($fixturePath);

        self::assertNotFalse($phpCode);

        $reader = new UseStatementReader();

        $actualImports = $reader->read($phpCode);

        self::assertSame(
            $expectedImports,
            $actualImports
        );
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: array<string, string>
     * }>
     */
    public static function provideUseStatements(): array
    {
        return [
            'Class import' => [
                'class-import.php.txt',
                [
                    'Clock' => 'App\Shared\Clock',
                ],
            ],

            'Multiple class imports' => [
                'multiple-imports.php.txt',
                [
                    'Clock' => 'App\Shared\Clock',
                    'Logger' => 'App\Shared\Logger',
                    'UserRepositoryInterface' => 'App\User\UserRepositoryInterface',
                ],
            ],

            'Aliased class import' => [
                'aliased-import.php.txt',
                [
                    'Repository' => 'App\Infrastructure\UserRepository',
                ],
            ],

            'Function import is ignored' => [
                'function-import.php.txt',
                [],
            ],

            'Constant import is ignored' => [
                'const-import.php.txt',
                [],
            ],

            'No imports' => [
                'no-imports.php.txt',
                [],
            ],
        ];
    }
}
