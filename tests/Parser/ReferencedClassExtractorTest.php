<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Parser;

use PhpDependencyExtractor\Parser\ReferencedClassExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReferencedClassExtractorTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/ReferencedClassExtractor';

    #[DataProvider('provideReferencedClasses')]
    public function testExtract(
        string $fixtureFile,
        array $expectedClasses
    ): void {
        $fixturePath = sprintf(
            '%s/%s',
            self::FIXTURES_DIR,
            $fixtureFile
        );

        $phpCode = file_get_contents($fixturePath);

        self::assertNotFalse($phpCode);

        $extractor = new ReferencedClassExtractor();

        $actualClasses = $extractor->extract($phpCode);

        self::assertSame(
            $expectedClasses,
            $actualClasses
        );
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: list<string>
     * }>
     */
    public static function provideReferencedClasses(): array
    {
        return [
            'Class import' => [
                'class-import.php.txt',
                [
                    'App\Shared\Clock',
                ],
            ],

            'Multiple class imports' => [
                'multiple-class-imports.php.txt',
                [
                    'App\Shared\Clock',
                    'App\Shared\Logger',
                    'App\User\UserRepositoryInterface',
                ],
            ],

            'Aliased import' => [
                'aliased-import.php.txt',
                [
                    'App\Infrastructure\UserRepository',
                ],
            ],

            'Extended class' => [
                'extended-class.php.txt',
                [
                    'App\Shared\AbstractService',
                ],
            ],

            'Implemented interfaces' => [
                'implemented-interfaces.php.txt',
                [
                    'App\Shared\LoggerAwareInterface',
                    'App\Shared\ServiceInterface',
                ],
            ],

            'Instantiated class' => [
                'instantiated-class.php.txt',
                [
                    'App\Shared\Clock',
                ],
            ],

            'Caught exception' => [
                'caught-exception.php.txt',
                [
                    'App\Shared\DomainException',
                ],
            ],

            'Multiple caught exceptions' => [
                'multiple-caught-exceptions.php.txt',
                [
                    'App\Shared\DomainException',
                    'App\Shared\InfrastructureException',
                ],
            ],

            'Static class reference' => [
                'static-class-reference.php.txt',
                [
                    'App\Shared\Clock',
                ],
            ],

            'Class from current namespace' => [
                'current-namespace-class.php.txt',
                [
                    'App\User\User',
                ],
            ],

            'Fully qualified class' => [
                'fully-qualified-class.php.txt',
                [
                    'App\Shared\Clock',
                ],
            ],

            'Duplicate references' => [
                'duplicate-references.php.txt',
                [
                    'App\Shared\Clock',
                ],
            ],

            'Function and constant imports' => [
                'ignored-function-and-const-imports.php.txt',
                [],
            ],

            'No referenced classes' => [
                'no-references.php.txt',
                [],
            ],
        ];
    }
}
