<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Parser;

/**
 * Resolves class names to fully qualified class names.
 */
final class ClassNameResolver
{
    /**
     * @param array<string, string> $imports
     */
    public function resolve(
        string $className,
        string $namespace,
        array $imports
    ): string {
        $isFullyQualified = str_starts_with(
            $className,
            '\\'
        );

        $className = trim(
            $className,
            '\\'
        );

        if ($className === '') {
            return '';
        }

        if ($isFullyQualified) {
            return $className;
        }

        if ($this->isSpecialClassName($className)) {
            return $className;
        }

        $parts = explode(
            '\\',
            $className
        );

        $firstPart = $parts[0];

        if (isset($imports[$firstPart])) {
            $importedClassName = $imports[$firstPart];

            $suffix = substr(
                $className,
                strlen($firstPart)
            );

            $resolvedClassName = sprintf(
                '%s%s',
                $importedClassName,
                $suffix
            );

            return $resolvedClassName;
        }

        if ($namespace === '') {
            return $className;
        }

        $resolvedClassName = sprintf(
            '%s\\%s',
            $namespace,
            $className
        );

        return $resolvedClassName;
    }

    private function isSpecialClassName(string $className): bool
    {
        if ($className === 'self') {
            return true;
        }

        if ($className === 'static') {
            return true;
        }

        if ($className === 'parent') {
            return true;
        }

        return false;
    }
}
