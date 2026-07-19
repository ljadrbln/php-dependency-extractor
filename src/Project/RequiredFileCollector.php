<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Project;

use PhpDependencyExtractor\Project\Contract\FileDependencyResolverInterface;

final class RequiredFileCollector
{
    public function __construct(
        private FileDependencyResolverInterface $dependencyResolver
    ) {}

    /**
     * @param string[] $entryFiles
     * @param array<string, string> $classMap
     *
     * @return string[]
     */
    public function collect(
        array $entryFiles,
        array $classMap
    ): array {
        $queue = $entryFiles;
        $requiredFiles = [];

        while ($queue !== []) {
            $file = array_shift($queue);

            if ($file === null) {
                continue;
            }

            if (isset($requiredFiles[$file])) {
                continue;
            }

            $requiredFiles[$file] = true;

            $dependencies = $this->dependencyResolver->resolve(
                $file
            );

            foreach ($dependencies as $dependency) {
                $dependencyFile = $classMap[$dependency] ?? null;

                if ($dependencyFile === null) {
                    continue;
                }

                if (isset($requiredFiles[$dependencyFile])) {
                    continue;
                }

                $queue[] = $dependencyFile;
            }
        }

        $requiredFiles = array_keys($requiredFiles);

        sort($requiredFiles);

        return $requiredFiles;
    }
}
