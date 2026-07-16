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
 * Collects referenced class names from PHP source code.
 */
final class ReferencedNameCollector
{
    /**
     * @return list<string>
     */
    public function collect(string $phpCode): array
    {
        $tokens = token_get_all($phpCode);
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

        $caughtExceptions = $this->collectCaughtExceptions($tokens);

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

            $classNames = $this->readClassNames(
                $tokens,
                $index + 1,
                $allowMultiple
            );

            $classes = array_merge(
                $classes,
                $classNames
            );
        }

        return $classes;
    }

    /**
     * @return list<string>
     */
    private function collectCaughtExceptions(array $tokens): array
    {
        $exceptions = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (!$this->isTokenType($token, T_CATCH)) {
                continue;
            }

            $start = $this->findNextTokenIndex(
                $tokens,
                $index + 1,
                '('
            );

            if ($start === null) {
                continue;
            }

            $exceptionNames = $this->readClassNames(
                $tokens,
                $start + 1,
                true
            );

            $exceptions = array_merge(
                $exceptions,
                $exceptionNames
            );
        }

        return $exceptions;
    }

    /**
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

            if ($allowMultiple && $this->isClassNameSeparator($token)) {
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
    private function addClassName(
        array &$names,
        string $name
    ): void {
        $name = rtrim(
            $name,
            '\\'
        );

        if ($name === '') {
            return;
        }

        $names[] = $name;
    }

    private function findNextTokenIndex(
        array $tokens,
        int $start,
        string $expectedToken
    ): ?int {
        $tokenCount = count($tokens);

        for ($index = $start; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if ($token === $expectedToken) {
                return $index;
            }

            if ($this->isWhitespaceToken($token)) {
                continue;
            }

            return null;
        }

        return null;
    }

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

    private function isClassNameSeparator(mixed $token): bool
    {
        if ($token === ',') {
            return true;
        }

        return $token === '|';
    }

    private function isWhitespaceToken(mixed $token): bool
    {
        return $this->isTokenType(
            $token,
            T_WHITESPACE
        );
    }

    private function isNameToken(mixed $token): bool
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

        return $this->isTokenType(
            $token,
            T_NAME_RELATIVE
        );
    }

    private function isTokenType(
        mixed $token,
        int $type
    ): bool {
        if (!is_array($token)) {
            return false;
        }

        return $token[0] === $type;
    }
}
