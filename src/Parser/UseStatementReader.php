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
 * Reads class import statements from a PHP source file.
 */
final class UseStatementReader
{
    /**
     * @return array<string, string>
     */
    public function read(string $phpCode): array
    {
        $tokens = token_get_all($phpCode);
        $imports = [];

        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            if (!$this->isClassUseToken($tokens, $index)) {
                continue;
            }

            $statementImports = $this->readUseStatement(
                $tokens,
                $index + 1
            );

            $imports = array_merge(
                $imports,
                $statementImports
            );
        }

        return $imports;
    }

    /**
     * @return array<string, string>
     */
    private function readUseStatement(
        array $tokens,
        int $start
    ): array {
        $imports = [];
        $className = '';
        $alias = null;

        $tokenCount = count($tokens);

        for ($index = $start; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if ($token === ';') {
                $this->addImport(
                    $imports,
                    $className,
                    $alias
                );

                break;
            }

            if ($token === ',') {
                $this->addImport(
                    $imports,
                    $className,
                    $alias
                );

                $className = '';
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

                $className = sprintf(
                    '%s%s',
                    $className,
                    $tokenText
                );

                continue;
            }

            if ($token !== '\\') {
                continue;
            }

            $className = sprintf(
                '%s\\',
                $className
            );
        }

        return $imports;
    }

    /**
     * @param array<string, string> $imports
     */
    private function addImport(
        array &$imports,
        string $className,
        ?string $alias
    ): void {
        $className = trim($className, '\\');

        if ($className === '') {
            return;
        }

        if ($alias === null) {
            $alias = $this->resolveShortClassName($className);
        }

        $imports[$alias] = $className;
    }

    private function resolveShortClassName(string $className): string
    {
        $parts = explode('\\', $className);
        $lastIndex = count($parts) - 1;

        return $parts[$lastIndex];
    }

    private function readNextIdentifier(
        array $tokens,
        int $start
    ): ?string {
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

    private function isClassUseToken(
        array $tokens,
        int $index
    ): bool {
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

        return $this->isTopLevelUse($tokens, $index);
    }

    private function isTopLevelUse(
        array $tokens,
        int $index
    ): bool {
        for ($previousIndex = $index - 1; $previousIndex >= 0; $previousIndex--) {
            $previousToken = $tokens[$previousIndex];

            if ($this->isWhitespaceToken($previousToken)) {
                continue;
            }

            if ($previousToken === ';') {
                return true;
            }

            if ($previousToken === '{') {
                return true;
            }

            return $this->isTokenType(
                $previousToken,
                T_OPEN_TAG
            );
        }

        return true;
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
