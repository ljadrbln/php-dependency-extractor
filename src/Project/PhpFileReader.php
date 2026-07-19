<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Project;

/**
 * Reads PHP source code from a file.
 */
final class PhpFileReader
{
    public function read(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }

        $phpCode = file_get_contents($file);

        if ($phpCode === false) {
            return null;
        }

        return $phpCode;
    }
}
