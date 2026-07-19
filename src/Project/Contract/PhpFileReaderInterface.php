<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Project\Contract;

/**
 * Reads PHP source code from a file.
 */
interface PhpFileReaderInterface
{
    public function read(string $file): ?string;
}
