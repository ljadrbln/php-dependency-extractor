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
 * Reads the namespace declared in a PHP source file.
 */
final class NamespaceReader
{
    public function read(string $phpCode): string
    {
        $tokens = token_get_all($phpCode);

        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (!$this->isNamespaceToken($token)) {
                continue;
            }

            $namespace = $this->readNamespaceName(
                $tokens,
                $index + 1
            );

            return $namespace;
        }

        return '';
    }

    private function readNamespaceName(
        array $tokens,
        int $start
    ): string {
        $namespace = '';

        $tokenCount = count($tokens);

        for ($index = $start; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if ($this->isWhitespaceToken($token)) {
                if ($namespace === '') {
                    continue;
                }

                break;
            }

            if ($this->isNameToken($token)) {
                $namespace .= $token[1];

                continue;
            }

            if ($token === '\\') {
                $namespace .= '\\';

                continue;
            }

            break;
        }

        $namespace = trim($namespace, '\\');

        return $namespace;
    }

    private function isNamespaceToken(mixed $token): bool
    {
        if (!is_array($token)) {
            return false;
        }

        return $token[0] === T_NAMESPACE;
    }

    private function isWhitespaceToken(mixed $token): bool
    {
        if (!is_array($token)) {
            return false;
        }

        return $token[0] === T_WHITESPACE;
    }

    private function isNameToken(mixed $token): bool
    {
        if (!is_array($token)) {
            return false;
        }

        if ($token[0] === T_STRING) {
            return true;
        }

        if ($token[0] === T_NAME_QUALIFIED) {
            return true;
        }

        return $token[0] === T_NAME_FULLY_QUALIFIED;
    }
}
