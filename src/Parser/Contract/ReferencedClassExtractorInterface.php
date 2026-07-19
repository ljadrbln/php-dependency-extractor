<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Parser\Contract;

/**
 * Extracts referenced classes from PHP source code.
 */
interface ReferencedClassExtractorInterface
{
    /**
     * @return list<string>
     */
    public function extract(string $phpCode): array;
}
