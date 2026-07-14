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
 * Extracts referenced PHP classes from PHP source code.
 *
 * The extractor parses PHP tokens and returns fully qualified class names
 * referenced by imports, inheritance, object creation, exception handling,
 * and static class references.
 */
final class ReferencedClassExtractor
{
    /**
     * @return list<string>
     */
    public function extract(string $phpCode): array
    {
        $tokens = token_get_all($phpCode);

        $namespace = $this->readNamespace($tokens);
        $useStatements = $this->readUseStatements($tokens);
        $referencedNames = $this->collectReferencedNames($tokens);

        $referencedClasses = array_values($useStatements);

        $resolvedClasses = $this->resolveReferencedNames(
            $referencedNames,
            $namespace,
            $useStatements
        );

        $referencedClasses = array_merge(
            $referencedClasses,
            $resolvedClasses
        );

        $referencedClasses = array_filter($referencedClasses);
        $referencedClasses = array_unique($referencedClasses);
        $referencedClasses = array_values($referencedClasses);

        return $referencedClasses;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     */
    private function readNamespace(array $tokens): string
    {
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (!$this->isTokenType($token, T_NAMESPACE)) {
                continue;
            }

            $namespace = $this->readName(
                $tokens,
                $index + 1
            );

            return $namespace;
        }

        return '';
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     *
     * @return array<string, string>
     */
    private function readUseStatements(array $tokens): array
    {
        $useStatements = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            if (!$this->isClassUseToken($tokens, $index)) {
                continue;
            }

            $statement = $this->readUseStatement(
                $tokens,
                $index + 1
            );

            $useStatements = array_merge(
                $useStatements,
                $statement
            );
        }

        return $useStatements;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     *
     * @return array<string, string>
     */
    private function readUseStatement(array $tokens, int $start): array
    {
        $statement = [];
        $name = '';
        $alias = null;
        $tokenCount = count($tokens);

        for ($index = $start; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if ($token === ';') {
                $this->addUseStatement(
                    $statement,
                    $name,
                    $alias
                );

                break;
            }

            if ($token === ',') {
                $this->addUseStatement(
                    $statement,
                    $name,
                    $alias
                );

                $name = '';
                $alias = null;

                continue;
            }

            if ($this->isTokenType($token, T_AS)) {
                $alias = $this->readNextIdentifier(
                    $tokens,
                    $index + 1
                );

                continue;
            }

            if ($alias !== null) {
                continue;
            }

            if ($this->isNameToken($token)) {
                $tokenText = $token[1];
                $tokenText = ltrim($tokenText, '\\');

                $name = sprintf(
                    '%s%s',
                    $name,
                    $tokenText
                );

                continue;
            }

            if ($token !== '\\') {
                continue;
            }

            $name = sprintf(
                '%s\\',
                $name
            );
        }

        return $statement;
    }

    /**
     * @param array<string, string> $statement
     */
    private function addUseStatement(
        array &$statement,
        string $name,
        ?string $alias
    ): void {
        $name = trim($name, '\\');

        if ($name === '') {
            return;
        }

        if ($alias === null) {
            $alias = $this->shortClassName($name);
        }

        $statement[$alias] = $name;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     *
     * @return list<string>
     */
    private function collectReferencedNames(array $tokens): array
    {
        $names = [];

        $extendedClasses = $this->collectClassesAfterKeyword(
            $tokens,
            T_EXTENDS,
            true
        );

        $names = array_merge(
            $names,
            $extendedClasses
        );

        $implementedClasses = $this->collectClassesAfterKeyword(
            $tokens,
            T_IMPLEMENTS,
            true
        );

        $names = array_merge(
            $names,
            $implementedClasses
        );

        $instantiatedClasses = $this->collectClassesAfterKeyword(
            $tokens,
            T_NEW,
            false
        );

        $names = array_merge(
            $names,
            $instantiatedClasses
        );

        $caughtExceptions = $this->collectClassesAfterKeyword(
            $tokens,
            T_CATCH,
            true
        );

        $names = array_merge(
            $names,
            $caughtExceptions
        );

        $staticClassReferences = $this->collectStaticClassReferences($tokens);

        $names = array_merge(
            $names,
            $staticClassReferences
        );

        $names = array_filter($names);
        $names = array_unique($names);
        $names = array_values($names);

        return $names;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     *
     * @return list<string>
     */
    private function collectClassesAfterKeyword(
        array $tokens,
        int $keyword,
        bool $allowMultiple
    ): array {
        $classes = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (!$this->isTokenType($token, $keyword)) {
                continue;
            }

            $names = $this->readClassNames(
                $tokens,
                $index + 1,
                $allowMultiple
            );

            $classes = array_merge(
                $classes,
                $names
            );
        }

        return $classes;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     *
     * @return list<string>
     */
    private function readClassNames(
        array $tokens,
        int $start,
        bool $allowMultiple
    ): array {
        $names = [];
        $name = '';
        $tokenCount = count($tokens);

        for ($index = $start; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if ($this->isWhitespaceToken($token)) {
                if ($name === '') {
                    continue;
                }

                if (!$allowMultiple) {
                    break;
                }

                continue;
            }

            if ($this->isNameToken($token)) {
                $tokenText = $token[1];

                $name = sprintf(
                    '%s%s',
                    $name,
                    $tokenText
                );

                continue;
            }

            if ($token === '\\') {
                $name = sprintf(
                    '%s\\',
                    $name
                );

                continue;
            }

            if ($allowMultiple && ($token === ',' || $token === '|')) {
                $this->addClassName(
                    $names,
                    $name
                );

                $name = '';

                continue;
            }

            break;
        }

        $this->addClassName(
            $names,
            $name
        );

        return $names;
    }

    /**
     * @param list<string> $names
     */
    private function addClassName(array &$names, string $name): void
    {
        $name = rtrim($name, '\\');

        if ($name === '') {
            return;
        }

        $names[] = $name;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     *
     * @return list<string>
     */
    private function collectStaticClassReferences(array $tokens): array
    {
        $classes = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (!$this->isNameToken($token)) {
                continue;
            }

            $separatorIndex = $this->findNextMeaningfulTokenIndex(
                $tokens,
                $index + 1
            );

            if ($separatorIndex === null) {
                continue;
            }

            $separator = $tokens[$separatorIndex];

            if (!$this->isTokenType($separator, T_DOUBLE_COLON)) {
                continue;
            }

            $classIndex = $this->findNextMeaningfulTokenIndex(
                $tokens,
                $separatorIndex + 1
            );

            if ($classIndex === null) {
                continue;
            }

            $classToken = $tokens[$classIndex];

            if (!$this->isTokenType($classToken, T_CLASS)) {
                continue;
            }

            $classes[] = $token[1];
        }

        return $classes;
    }

    /**
     * @param list<string>          $referencedNames
     * @param array<string, string> $useStatements
     *
     * @return list<string>
     */
    private function resolveReferencedNames(
        array $referencedNames,
        string $namespace,
        array $useStatements
    ): array {
        $referencedClasses = [];

        foreach ($referencedNames as $referencedName) {
            $referencedClass = $this->resolveReferencedClass(
                $referencedName,
                $namespace,
                $useStatements
            );

            $referencedClasses[] = $referencedClass;
        }

        return $referencedClasses;
    }

    /**
     * @param array<string, string> $useStatements
     */
    private function resolveReferencedClass(
        string $className,
        string $namespace,
        array $useStatements
    ): string {
        $isFullyQualified = str_starts_with($className, '\\');

        $className = trim($className, '\\');

        if ($className === '') {
            return '';
        }

        if ($isFullyQualified) {
            return $className;
        }

        $parts = explode('\\', $className);
        $firstPart = $parts[0];

        if (isset($useStatements[$firstPart])) {
            $resolvedClassName = $useStatements[$firstPart];

            $suffix = substr(
                $className,
                strlen($firstPart)
            );

            return sprintf(
                '%s%s',
                $resolvedClassName,
                $suffix
            );
        }

        if ($namespace === '') {
            return $className;
        }

        return sprintf(
            '%s\\%s',
            $namespace,
            $className
        );
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     */
    private function readName(array $tokens, int $start): string
    {
        $name = '';
        $tokenCount = count($tokens);

        for ($index = $start; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if ($this->isWhitespaceToken($token)) {
                if ($name === '') {
                    continue;
                }

                break;
            }

            if ($this->isNameToken($token)) {
                $tokenText = $token[1];
                $tokenText = ltrim($tokenText, '\\');

                $name = sprintf(
                    '%s%s',
                    $name,
                    $tokenText
                );

                continue;
            }

            if ($token !== '\\') {
                break;
            }

            $name = sprintf(
                '%s\\',
                $name
            );
        }

        $name = trim($name, '\\');

        return $name;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     */
    private function readNextIdentifier(array $tokens, int $start): ?string
    {
        $tokenCount = count($tokens);

        for ($index = $start; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (!$this->isTokenType($token, T_STRING)) {
                continue;
            }

            return $token[1];
        }

        return null;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     */
    private function isClassUseToken(array $tokens, int $index): bool
    {
        $token = $tokens[$index];

        if (!$this->isTokenType($token, T_USE)) {
            return false;
        }

        $nextIndex = $this->findNextMeaningfulTokenIndex(
            $tokens,
            $index + 1
        );

        if ($nextIndex === null) {
            return false;
        }

        $nextToken = $tokens[$nextIndex];

        if ($this->isTokenType($nextToken, T_FUNCTION)) {
            return false;
        }

        if ($this->isTokenType($nextToken, T_CONST)) {
            return false;
        }

        for ($previousIndex = $index - 1; $previousIndex >= 0; $previousIndex--) {
            $previousToken = $tokens[$previousIndex];

            if ($this->isWhitespaceToken($previousToken)) {
                continue;
            }

            if ($this->isTopLevelBoundary($previousToken)) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     */
    private function findNextMeaningfulTokenIndex(
        array $tokens,
        int $start
    ): ?int {
        $tokenCount = count($tokens);

        for ($index = $start; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if ($this->isWhitespaceToken($token)) {
                continue;
            }

            if ($this->isTokenType($token, T_COMMENT)) {
                continue;
            }

            if ($this->isTokenType($token, T_DOC_COMMENT)) {
                continue;
            }

            return $index;
        }

        return null;
    }

    private function isTopLevelBoundary(array|string $token): bool
    {
        if ($token === ';') {
            return true;
        }

        if ($token === '{') {
            return true;
        }

        return $this->isTokenType($token, T_OPEN_TAG);
    }

    private function isWhitespaceToken(array|string $token): bool
    {
        return $this->isTokenType($token, T_WHITESPACE);
    }

    private function isNameToken(array|string $token): bool
    {
        if ($this->isTokenType($token, T_STRING)) {
            return true;
        }

        if ($this->isTokenType($token, T_NAME_QUALIFIED)) {
            return true;
        }

        if ($this->isTokenType($token, T_NAME_FULLY_QUALIFIED)) {
            return true;
        }

        return $this->isTokenType($token, T_NAME_RELATIVE);
    }

    private function isTokenType(array|string $token, int $type): bool
    {
        if (!is_array($token)) {
            return false;
        }

        return $token[0] === $type;
    }

    private function shortClassName(string $className): string
    {
        $parts = explode('\\', $className);
        $lastIndex = count($parts) - 1;

        return $parts[$lastIndex];
    }
}
