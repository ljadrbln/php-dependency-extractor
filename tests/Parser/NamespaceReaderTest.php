<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Parser;

use PhpDependencyExtractor\Parser\NamespaceReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NamespaceReaderTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/NamespaceReader';

    #[DataProvider('provideNamespaces')]
    public function testRead(
        string $fixtureFile,
        string $expectedNamespace
    ): void {
        $fixturePath = sprintf(
            '%s/%s',
            self::FIXTURES_DIR,
            $fixtureFile
        );

        self::assertFileExists($fixturePath);

        $phpCode = file_get_contents($fixturePath);

        self::assertNotFalse($phpCode);

        $reader = new NamespaceReader();

        $actualNamespace = $reader->read($phpCode);

        self::assertSame(
            $expectedNamespace,
            $actualNamespace
        );
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: string
     * }>
     */
    public static function provideNamespaces(): array
    {
        return [
            'Simple namespace' => [
                'simple-namespace.php.txt',
                'App',
            ],

            'Qualified namespace' => [
                'qualified-namespace.php.txt',
                'App\User\SignUp',
            ],

            'Namespace with extra whitespace' => [
                'namespace-with-whitespace.php.txt',
                'App\User',
            ],

            'No namespace' => [
                'no-namespace.php.txt',
                '',
            ],
        ];
    }
}
