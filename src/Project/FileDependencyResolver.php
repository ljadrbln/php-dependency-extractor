<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Project;

use PhpDependencyExtractor\Parser\Contract\ReferencedClassExtractorInterface;
use PhpDependencyExtractor\Project\Contract\FileDependencyResolverInterface;
use PhpDependencyExtractor\Project\Contract\PhpFileReaderInterface;

final class FileDependencyResolver implements FileDependencyResolverInterface
{
    public function __construct(
        private PhpFileReaderInterface $phpFileReader,
        private ReferencedClassExtractorInterface $referencedClassExtractor
    ) {}

    /**
     * @return list<string>
     */
    public function resolve(string $file): array
    {
        $phpCode = $this->phpFileReader->read($file);

        if ($phpCode === null) {
            return [];
        }

        return $this->referencedClassExtractor->extract(
            $phpCode
        );
    }
}
