<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Parser;

use PhpDependencyExtractor\Parser\ReferencedNameCollector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReferencedNameCollectorTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ReferencedNameCollector';

    #[DataProvider('provideReferencedNames')]
    public function testCollect(
        string $fixtureFile,
        array $expectedNames
    ): void {
        $fixturePath = sprintf(
            '%s/%s',
            self::FIXTURES_DIR,
            $fixtureFile
        );

        self::assertFileExists($fixturePath);

        $phpCode = file_get_contents($fixturePath);

        self::assertNotFalse($phpCode);

        $collector = new ReferencedNameCollector();

        $actualNames = $collector->collect($phpCode);

        self::assertSame(
            $expectedNames,
            $actualNames
        );
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: list<string>
     * }>
     */
    public static function provideReferencedNames(): array
    {
        return [
            'Extended class' => [
                'extended-class.php.txt',
                [
                    'AbstractService',
                ],
            ],

            'Implemented interfaces' => [
                'implemented-interfaces.php.txt',
                [
                    'LoggerAwareInterface',
                    'ServiceInterface',
                ],
            ],

            'Instantiated class' => [
                'instantiated-class.php.txt',
                [
                    'Clock',
                ],
            ],

            'Caught exception' => [
                'caught-exception.php.txt',
                [
                    'DomainException',
                ],
            ],

            'Multiple caught exceptions' => [
                'multiple-caught-exceptions.php.txt',
                [
                    'DomainException',
                    'InfrastructureException',
                ],
            ],

            'Static class reference' => [
                'static-class-reference.php.txt',
                [
                    'Clock',
                ],
            ],

            'Multiple references' => [
                'multiple-references.php.txt',
                [
                    'AbstractService',
                    'LoggerAwareInterface',
                    'ServiceInterface',
                    'Clock',
                    'DomainException',
                ],
            ],

            'Duplicate references' => [
                'duplicate-references.php.txt',
                [
                    'Clock',
                ],
            ],

            'No references' => [
                'no-references.php.txt',
                [],
            ],
        ];
    }
}
