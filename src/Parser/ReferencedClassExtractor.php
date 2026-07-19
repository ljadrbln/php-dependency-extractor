<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Parser;

use PhpDependencyExtractor\Parser\Contract\ReferencedClassExtractorInterface;

/**
 * Extracts referenced classes from a PHP source file.
 */
final class ReferencedClassExtractor implements ReferencedClassExtractorInterface
{
    public function __construct(
        private readonly NamespaceReader $namespaceReader,
        private readonly UseStatementReader $useStatementReader,
        private readonly ReferencedNameCollector $referencedNameCollector,
        private readonly ClassNameResolver $classNameResolver,
    ) {}

    /**
     * @return list<string>
     */
    /**
     * @return list<string>
     */
    public function extract(string $phpCode): array
    {
        $namespace = $this->namespaceReader->read($phpCode);

        $imports = $this->useStatementReader->read($phpCode);

        $referencedNames = $this->referencedNameCollector->collect($phpCode);

        $classes = array_values($imports);

        foreach ($referencedNames as $referencedName) {
            $className = $this->classNameResolver->resolve(
                $referencedName,
                $namespace,
                $imports
            );

            $classes[] = $className;
        }

        $classes = array_unique($classes);
        $classes = array_values($classes);

        return $classes;
    }
}
