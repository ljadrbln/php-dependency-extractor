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
 * Builds a map of declared classes.
 */
final class ClassMapBuilder
{
    public function __construct(
        private readonly PhpFileCollector $phpFileCollector,
        private readonly PhpFileReader $phpFileReader,
        private readonly DeclaredClassReader $declaredClassReader
    ) {}

    /**
     * @return array<string, string>
     */
    public function build(string $path): array
    {
        $files = $this->phpFileCollector->collect($path);

        $classMap = [];

        foreach ($files as $file) {
            $phpCode = $this->phpFileReader->read($file);

            if ($phpCode === null) {
                continue;
            }

            $className = $this->declaredClassReader->read($phpCode);

            if ($className === null) {
                continue;
            }

            $classMap[$className] = $file;
        }

        ksort($classMap);

        return $classMap;
    }
}
