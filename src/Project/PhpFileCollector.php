<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Dependency Extractor project.
 *
 * Copyright (c) 2026 Pavel Konovalov
 * Licensed under the MIT License.
 */

namespace PhpDependencyExtractor\Project;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Collects PHP files from a project directory.
 */
final class PhpFileCollector
{
    /**
     * @return list<string>
     */
    public function collect(string $path): array
    {
        if (is_file($path)) {
            return $this->collectFile($path);
        }

        if (!is_dir($path)) {
            return [];
        }

        $directoryIterator = new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::SKIP_DOTS
        );

        $iterator = new RecursiveIteratorIterator(
            $directoryIterator
        );

        $files = [];

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            if (!$file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private function collectFile(string $path): array
    {
        $extension = pathinfo(
            $path,
            PATHINFO_EXTENSION
        );

        if ($extension !== 'php') {
            return [];
        }

        return [$path];
    }
}
