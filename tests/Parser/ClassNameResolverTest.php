<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Parser;

use PhpDependencyExtractor\Parser\ClassNameResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClassNameResolverTest extends TestCase
{
    #[DataProvider('provideClassNames')]
    public function testResolve(
        string $className,
        string $namespace,
        array $imports,
        string $expectedClassName
    ): void {
        $resolver = new ClassNameResolver();

        $actualClassName = $resolver->resolve(
            $className,
            $namespace,
            $imports
        );

        self::assertSame(
            $expectedClassName,
            $actualClassName
        );
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: string,
     *     2: array<string, string>,
     *     3: string
     * }>
     */
    public static function provideClassNames(): array
    {
        return [
            'Imported class' => [
                'Clock',
                'App\User',
                [
                    'Clock' => 'App\Shared\Clock',
                ],
                'App\Shared\Clock',
            ],

            'Imported class with namespace suffix' => [
                'Repository\Query',
                'App\User',
                [
                    'Repository' => 'App\Infrastructure\Repository',
                ],
                'App\Infrastructure\Repository\Query',
            ],

            'Class from current namespace' => [
                'User',
                'App\User',
                [],
                'App\User\User',
            ],

            'Qualified class from current namespace' => [
                'Service\UserService',
                'App\User',
                [],
                'App\User\Service\UserService',
            ],

            'Fully qualified class' => [
                '\App\Shared\Clock',
                'App\User',
                [],
                'App\Shared\Clock',
            ],

            'Class without namespace' => [
                'Clock',
                '',
                [],
                'Clock',
            ],

            'Self class reference' => [
                'self',
                'App\User',
                [],
                'self',
            ],

            'Static class reference' => [
                'static',
                'App\User',
                [],
                'static',
            ],

            'Parent class reference' => [
                'parent',
                'App\User',
                [],
                'parent',
            ],

            'Empty class name' => [
                '',
                'App\User',
                [],
                '',
            ],
        ];
    }
}
