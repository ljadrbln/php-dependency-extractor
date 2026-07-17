<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Project;

use PhpDependencyExtractor\Parser\NamespaceReader;

/**
 * Reads the fully qualified class name declared in PHP source code.
 */
final class DeclaredClassReader
{
    public function __construct(
        private readonly NamespaceReader $namespaceReader
    ) {}

    public function read(string $phpCode): ?string
    {
        $namespace = $this->namespaceReader->read($phpCode);
        $tokens = token_get_all($phpCode);

        $className = $this->readDeclaredClassName($tokens);

        if ($className === null) {
            return null;
        }

        if ($namespace === '') {
            return $className;
        }

        $fullyQualifiedClassName = sprintf(
            '%s\\%s',
            $namespace,
            $className
        );

        return $fullyQualifiedClassName;
    }

    private function readDeclaredClassName(array $tokens): ?string
    {
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (!$this->isDeclarationToken($token)) {
                continue;
            }

            if ($this->isAnonymousClass($tokens, $index)) {
                continue;
            }

            $className = $this->readNextIdentifier(
                $tokens,
                $index + 1
            );

            if ($className === null) {
                continue;
            }

            return $className;
        }

        return null;
    }

    private function isDeclarationToken(mixed $token): bool
    {
        if (!is_array($token)) {
            return false;
        }

        if ($token[0] === T_CLASS) {
            return true;
        }

        if ($token[0] === T_INTERFACE) {
            return true;
        }

        if ($token[0] === T_TRAIT) {
            return true;
        }

        return $token[0] === T_ENUM;
    }

    private function isAnonymousClass(
        array $tokens,
        int $classIndex
    ): bool {
        for ($index = $classIndex - 1; $index >= 0; $index--) {
            $token = $tokens[$index];

            if ($this->isWhitespaceToken($token)) {
                continue;
            }

            return $this->isTokenType(
                $token,
                T_NEW
            );
        }

        return false;
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

    private function isWhitespaceToken(mixed $token): bool
    {
        return $this->isTokenType(
            $token,
            T_WHITESPACE
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
