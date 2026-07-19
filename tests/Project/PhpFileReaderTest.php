<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Tests\Project;

use PhpDependencyExtractor\Project\PhpFileReader;
use PHPUnit\Framework\TestCase;

final class PhpFileReaderTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures/PhpFileReader';

    public function testRead(): void
    {
        $reader = new PhpFileReader();

        $actualPhpCode = $reader->read(
            self::FIXTURES_DIR . '/Example.php'
        );

        $expectedPhpCode = file_get_contents(
            self::FIXTURES_DIR . '/Example.php'
        );

        self::assertSame(
            $expectedPhpCode,
            $actualPhpCode
        );
    }

    public function testReadNonExistingFile(): void
    {
        $reader = new PhpFileReader();

        $actualPhpCode = $reader->read(
            self::FIXTURES_DIR . '/Missing.php'
        );

        self::assertNull(
            $actualPhpCode
        );
    }

    public function testReadEmptyFile(): void
    {
        $reader = new PhpFileReader();

        $actualPhpCode = $reader->read(
            self::FIXTURES_DIR . '/Empty.php'
        );

        self::assertSame(
            '',
            $actualPhpCode
        );
    }
}
