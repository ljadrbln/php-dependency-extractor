<?php

declare(strict_types=1);

/**
 * PHP Dependency Extractor.
 */

const SOURCE_CODE_ROOT = 'backend/src';

main();

function main(): void
{
    $options = readOptions();

    validateOptions($options);

    $classMap = buildClassMap($options);

    $entryFiles = collectEntryFiles($options);

    $requiredFiles = collectRequiredFiles(
        $entryFiles,
        $classMap
    );

    $syncPlan = buildSyncPlan(
        $requiredFiles,
        $options
    );

    if ($options['apply']) {
        applySyncPlan($syncPlan);
    }

    printSyncPlan($syncPlan);
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function readOptions(): array
{
    $rawOptions = getopt('', [
        'source:',
        'target:',
        'entry:',
        'apply',
        'update',
    ]);

    $sourceRoot = $rawOptions['source'] ?? '';
    $targetRoot = $rawOptions['target'] ?? '';
    $entries = $rawOptions['entry'] ?? [];

    $sourceRoot = normalizeRoot((string)$sourceRoot);
    $targetRoot = normalizeRoot((string)$targetRoot);
    $entries = normalizeEntries($entries);

    $apply = array_key_exists('apply', $rawOptions);
    $update = array_key_exists('update', $rawOptions);

    return [
        'sourceRoot' => $sourceRoot,
        'targetRoot' => $targetRoot,
        'entries' => $entries,
        'apply' => $apply,
        'update' => $update,
    ];
}

function normalizeRoot(string $path): string
{
    $path = trim($path);
    $path = rtrim($path, '/');

    return $path;
}

function normalizeEntries(mixed $entries): array
{
    if (is_string($entries)) {
        $entries = [$entries];
    }

    if (!is_array($entries)) {
        return [];
    }

    $normalized = [];

    foreach ($entries as $entry) {
        $entry = (string)$entry;
        $entry = trim($entry);
        $entry = trim($entry, '/');

        if ($entry === '') {
            continue;
        }

        $normalized[] = $entry;
    }

    return $normalized;
}

function validateOptions(array $options): void
{
    $sourceRoot = $options['sourceRoot'];
    $targetRoot = $options['targetRoot'];
    $entries = $options['entries'];

    if ($sourceRoot === '') {
        fail('Required option is missing: --source');
    }

    if ($targetRoot === '') {
        fail('Required option is missing: --target');
    }

    if ($entries === []) {
        fail('Required option is missing: --entry');
    }

    if (!is_dir($sourceRoot)) {
        $message = sprintf(
            'Source project not found: %s',
            $sourceRoot
        );

        fail($message);
    }

    if (!is_dir($targetRoot)) {
        $message = sprintf(
            'Target project not found: %s',
            $targetRoot
        );

        fail($message);
    }

    $sourceCodeDir = sprintf(
        '%s/%s',
        $sourceRoot,
        SOURCE_CODE_ROOT
    );

    if (!is_dir($sourceCodeDir)) {
        $message = sprintf(
            'Source code root not found: %s',
            $sourceCodeDir
        );

        fail($message);
    }

    foreach ($entries as $entry) {
        $entryPath = sprintf(
            '%s/%s',
            $sourceRoot,
            $entry
        );

        if (file_exists($entryPath)) {
            continue;
        }

        $message = sprintf(
            'Entry not found in source: %s',
            $entryPath
        );

        fail($message);
    }
}

function buildClassMap(array $options): array
{
    $sourceRoot = $options['sourceRoot'];

    $sourceCodeRoot = sprintf(
        '%s/%s',
        $sourceRoot,
        SOURCE_CODE_ROOT
    );

    $files = collectPhpFiles($sourceCodeRoot);

    $classMap = [];

    foreach ($files as $file) {
        $fqcn = extractDeclaredFqcn($file);

        if ($fqcn === null) {
            continue;
        }

        $classMap[$fqcn] = $file;
    }

    return $classMap;
}

function collectPhpFiles(string $path): array
{
    if (is_file($path)) {
        return collectPhpFile($path);
    }

    if (!is_dir($path)) {
        return [];
    }

    $files = [];

    $directory = new RecursiveDirectoryIterator(
        $path,
        FilesystemIterator::SKIP_DOTS
    );

    $iterator = new RecursiveIteratorIterator($directory);

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        if ($file->getExtension() !== 'php') {
            continue;
        }

        $files[] = $file->getPathname();
    }

    $files = array_unique($files);
    $files = array_values($files);

    return $files;
}

function extractDeclaredFqcn(string $file): ?string
{
    $content = file_get_contents($file);

    if ($content === false) {
        $message = sprintf(
            'Failed to read file: %s',
            $file
        );

        fail($message);
    }

    $tokens = token_get_all($content);

    $namespace = '';
    $className = null;

    $tokenCount = count($tokens);

    for ($i = 0; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if (isNamespaceToken($token)) {
            $namespace = readName($tokens, $i + 1);
            continue;
        }

        if (!isClassDeclarationToken($token)) {
            continue;
        }

        $className = readNextIdentifier($tokens, $i + 1);

        break;
    }

    if ($className === null) {
        return null;
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

function isNamespaceToken(mixed $token): bool
{
    if (!is_array($token)) {
        return false;
    }

    return $token[0] === T_NAMESPACE;
}

function isClassDeclarationToken(mixed $token): bool
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

    if (defined('T_ENUM') && $token[0] === T_ENUM) {
        return true;
    }

    return false;
}

function isNameToken(mixed $token): bool
{
    if (!is_array($token)) {
        return false;
    }

    if ($token[0] === T_STRING) {
        return true;
    }

    if (defined('T_NAME_QUALIFIED') && $token[0] === T_NAME_QUALIFIED) {
        return true;
    }

    if (
        defined('T_NAME_FULLY_QUALIFIED')
        && $token[0] === T_NAME_FULLY_QUALIFIED
    ) {
        return true;
    }

    return false;
}

function readName(array $tokens, int $start): string
{
    $name = '';
    $tokenCount = count($tokens);

    for ($i = $start; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if (isWhitespaceToken($token)) {
            if ($name === '') {
                continue;
            }

            break;
        }

        if (isNameToken($token)) {
            $tokenText = ltrim($token[1], '\\');

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

        break;
    }

    $name = trim($name, '\\');

    return $name;
}

function isWhitespaceToken(mixed $token): bool
{
    if (!is_array($token)) {
        return false;
    }

    return $token[0] === T_WHITESPACE;
}

function readNextIdentifier(array $tokens, int $start): ?string
{
    $tokenCount = count($tokens);

    for ($i = $start; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if (!is_array($token)) {
            continue;
        }

        if ($token[0] !== T_STRING) {
            continue;
        }

        return $token[1];
    }

    return null;
}

function collectPhpFile(string $path): array
{
    if (!str_ends_with($path, '.php')) {
        return [];
    }

    return [$path];
}

function collectEntryFiles(array $options): array
{
    $sourceRoot = $options['sourceRoot'];
    $entries = $options['entries'];

    $files = [];

    foreach ($entries as $entry) {
        $entryPath = sprintf(
            '%s/%s',
            $sourceRoot,
            $entry
        );

        $entryFiles = collectPhpFiles($entryPath);

        $files = array_merge(
            $files,
            $entryFiles
        );
    }

    $files = array_unique($files);
    $files = array_values($files);

    return $files;
}

function collectRequiredFiles(
    array $entryFiles,
    array $classMap
): array {
    $queue = $entryFiles;
    $requiredFiles = [];

    while ($queue !== []) {
        $file = array_shift($queue);

        if (isset($requiredFiles[$file])) {
            continue;
        }

        $requiredFiles[$file] = true;

        $dependencies = collectReferencedClasses($file);

        foreach ($dependencies as $dependency) {
            if (!isset($classMap[$dependency])) {
                continue;
            }

            $dependencyFile = $classMap[$dependency];

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

function collectReferencedClasses(string $file): array
{
    $content = file_get_contents($file);

    if ($content === false) {
        $message = sprintf(
            'Failed to read file: %s',
            $file
        );

        fail($message);
    }

    $tokens = token_get_all($content);

    $namespace = readNamespace($tokens);
    $useStatements = readUseStatements($tokens);
    $referencedNames = collectReferencedNames($tokens);

    $referencedClasses = resolveReferencedNames(
        $referencedNames,
        $namespace,
        $useStatements
    );

    $referencedClasses = array_unique($referencedClasses);
    $referencedClasses = array_values($referencedClasses);

    return $referencedClasses;
}

function readNamespace(array $tokens): string
{
    $tokenCount = count($tokens);

    for ($i = 0; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if (!isNamespaceToken($token)) {
            continue;
        }

        return readName(
            $tokens,
            $i + 1
        );
    }

    return '';
}

function readUseStatements(array $tokens): array
{
    $useStatements = [];

    $tokenCount = count($tokens);

    for ($i = 0; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if (!isUseToken($tokens, $i)) {
            continue;
        }

        $statement = readUseStatement(
            $tokens,
            $i + 1
        );

        $useStatements = array_merge(
            $useStatements,
            $statement
        );
    }

    return $useStatements;
}

function readUseStatement(array $tokens, int $start): array
{
    $statement = [];

    $name = '';
    $alias = null;

    $tokenCount = count($tokens);

    for ($i = $start; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if ($token === ';') {
            addUseStatement(
                $statement,
                $name,
                $alias
            );

            break;
        }

        if ($token === ',') {
            addUseStatement(
                $statement,
                $name,
                $alias
            );

            $name = '';
            $alias = null;

            continue;
        }

        if (isAsToken($token)) {
            $alias = readNextIdentifier(
                $tokens,
                $i + 1
            );

            continue;
        }

        if (isNameToken($token)) {
            if ($alias === null) {
                $name .= ltrim($token[1], '\\');
            }

            continue;
        }

        if ($token === '\\') {
            if ($alias === null) {
                $name .= '\\';
            }

            continue;
        }
    }

    return $statement;
}

function addUseStatement(
    array &$statement,
    string $name,
    ?string $alias
): void {
    if ($name === '') {
        return;
    }

    $name = trim($name, '\\');

    if ($alias === null) {
        $parts = explode(
            '\\',
            $name
        );

        $lastIndex = count($parts) - 1;

        $alias = $parts[$lastIndex];
    }

    $statement[$alias] = $name;
}

function isUseToken(array $tokens, int $index): bool
{
    $token = $tokens[$index];

    if (!is_array($token)) {
        return false;
    }

    if ($token[0] !== T_USE) {
        return false;
    }

    for ($i = $index - 1; $i >= 0; $i--) {
        $token = $tokens[$i];

        if (isWhitespaceToken($token)) {
            continue;
        }

        if ($token === ';') {
            return true;
        }

        if ($token === '{') {
            return true;
        }

        if (isOpenTagToken($token)) {
            return true;
        }

        return false;
    }

    return true;
}

function isAsToken(mixed $token): bool
{
    if (!is_array($token)) {
        return false;
    }

    return $token[0] === T_AS;
}

function isOpenTagToken(mixed $token): bool
{
    if (!is_array($token)) {
        return false;
    }

    return $token[0] === T_OPEN_TAG;
}

function collectReferencedNames(array $tokens): array
{
    $names = [];

    $extendedClasses = collectExtendedClasses($tokens);

    $names = array_merge(
        $names,
        $extendedClasses
    );

    $implementedClasses = collectImplementedClasses($tokens);

    $names = array_merge(
        $names,
        $implementedClasses
    );

    $instantiatedClasses = collectInstantiatedClasses($tokens);

    $names = array_merge(
        $names,
        $instantiatedClasses
    );

    $caughtExceptions = collectCaughtExceptions($tokens);

    $names = array_merge(
        $names,
        $caughtExceptions
    );

    $staticClassReferences = collectStaticClassReferences($tokens);

    $names = array_merge(
        $names,
        $staticClassReferences
    );

    $names = array_unique($names);
    $names = array_values($names);

    return $names;
}

function collectExtendedClasses(array $tokens): array
{
    return collectClassesAfterKeyword(
        $tokens,
        T_EXTENDS
    );
}

function collectImplementedClasses(array $tokens): array
{
    return collectClassesAfterKeyword(
        $tokens,
        T_IMPLEMENTS
    );
}

function collectInstantiatedClasses(array $tokens): array
{
    return collectClassesAfterKeyword(
        $tokens,
        T_NEW
    );
}

function collectCaughtExceptions(array $tokens): array
{
    return collectClassesAfterKeyword(
        $tokens,
        T_CATCH
    );
}

function collectClassesAfterKeyword(
    array $tokens,
    int $keyword
): array {
    $classes = [];

    $tokenCount = count($tokens);

    for ($i = 0; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if (!isArrayTokenType($token, $keyword)) {
            continue;
        }

        $className = readName(
            $tokens,
            $i + 1
        );

        if ($className === '') {
            continue;
        }

        $classes[] = $className;
    }

    return $classes;
}

function collectStaticClassReferences(array $tokens): array
{
    $classes = [];

    $tokenCount = count($tokens);

    for ($i = 0; $i < $tokenCount - 2; $i++) {
        $token = $tokens[$i];

        if (!isNameToken($token)) {
            continue;
        }

        $separator = $tokens[$i + 1];

        if ($separator !== T_DOUBLE_COLON && $separator !== '::') {
            if (!is_array($separator) || $separator[0] !== T_DOUBLE_COLON) {
                continue;
            }
        }

        $nextToken = $tokens[$i + 2];

        if (!is_array($nextToken)) {
            continue;
        }

        if ($nextToken[0] !== T_CLASS) {
            continue;
        }

        $classes[] = $token[1];
    }

    return $classes;
}

function isArrayTokenType(
    mixed $token,
    int $type
): bool {
    if (!is_array($token)) {
        return false;
    }

    return $token[0] === $type;
}

function resolveReferencedNames(
    array $referencedNames,
    string $namespace,
    array $useStatements
): array {
    $referencedClasses = [];

    foreach ($referencedNames as $referencedName) {
        $referencedClass = resolveReferencedClass(
            $referencedName,
            $namespace,
            $useStatements
        );

        $referencedClasses[] = $referencedClass;
    }

    return $referencedClasses;
}

function resolveReferencedClass(
    string $className,
    string $namespace,
    array $useStatements
): string {
    $className = trim($className, '\\');

    if ($className === '') {
        return '';
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

    if (str_contains($className, '\\')) {
        return $className;
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

function buildSyncPlan(
    array $requiredFiles,
    array $options
): array {
    $sourceRoot = $options['sourceRoot'];
    $targetRoot = $options['targetRoot'];

    $items = [];

    foreach ($requiredFiles as $sourceFile) {
        $relativePath = relativePath(
            $sourceRoot,
            $sourceFile
        );

        $targetFile = sprintf(
            '%s/%s',
            $targetRoot,
            $relativePath
        );

        $status = resolveSyncStatus(
            $sourceFile,
            $targetFile
        );

        $items[] = [
            'status' => $status,
            'sourceFile' => $sourceFile,
            'targetFile' => $targetFile,
            'relativePath' => $relativePath,
        ];
    }

    $obsoleteItems = collectObsoletePlanItems(
        $items,
        $options
    );

    $items = array_merge(
        $items,
        $obsoleteItems
    );

    return [
        'items' => $items,
        'apply' => $options['apply'],
        'update' => $options['update'],
    ];
}

function applySyncPlan(array $syncPlan): void
{
    $items = $syncPlan['items'];
    $update = $syncPlan['update'];

    foreach ($items as $item) {
        $status = $item['status'];

        if ($status === 'copy') {
            copyPlanItem($item);
            continue;
        }

        if ($status !== 'differs') {
            continue;
        }

        if (!$update) {
            continue;
        }

        copyPlanItem($item);
    }
}

function printSyncPlan(array $syncPlan): void
{
    $items = $syncPlan['items'];

    $lines = [];

    $mode = $syncPlan['apply'] ? 'SYNC MODE' : 'PLAN MODE';

    $lines[] = $mode;
    $lines[] = '';

    foreach ($items as $item) {
        $line = formatPlanItem($item);

        $lines[] = $line;
    }

    $summaryLines = buildSummaryLines($items);

    $lines = array_merge(
        $lines,
        $summaryLines
    );

    echo implode(PHP_EOL, $lines);
    echo PHP_EOL;
}


function resolveSyncStatus(
    string $sourceFile,
    string $targetFile
): string {
    if (!file_exists($targetFile)) {
        return 'copy';
    }

    $sourceHash = sha1_file($sourceFile);
    $targetHash = sha1_file($targetFile);

    if ($sourceHash === $targetHash) {
        return 'exists';
    }

    return 'differs';
}

function collectObsoletePlanItems(
    array $items,
    array $options
): array {
    $targetRoot = $options['targetRoot'];

    $targetSourceRoot = sprintf(
        '%s/%s',
        $targetRoot,
        SOURCE_CODE_ROOT
    );

    $requiredPaths = [];

    foreach ($items as $item) {
        $relativePath = $item['relativePath'];
        $requiredPaths[$relativePath] = true;
    }

    $targetFiles = collectPhpFiles($targetSourceRoot);
    $obsoleteItems = [];

    foreach ($targetFiles as $targetFile) {
        $relativePath = relativePath(
            $targetRoot,
            $targetFile
        );

        if (isset($requiredPaths[$relativePath])) {
            continue;
        }

        $obsoleteItems[] = [
            'status' => 'obsolete',
            'sourceFile' => null,
            'targetFile' => $targetFile,
            'relativePath' => $relativePath,
        ];
    }

    return $obsoleteItems;
}

function copyPlanItem(array $item): void
{
    $sourceFile = $item['sourceFile'];
    $targetFile = $item['targetFile'];

    if ($sourceFile === null) {
        return;
    }

    $targetDir = dirname($targetFile);

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $copied = copy(
        $sourceFile,
        $targetFile
    );

    if ($copied) {
        return;
    }

    $message = sprintf(
        'Failed to copy %s -> %s',
        $sourceFile,
        $targetFile
    );

    fail($message);
}

function formatPlanItem(array $item): string
{
    $status = $item['status'];
    $relativePath = $item['relativePath'];

    $label = formatStatusLabel($status);

    return sprintf(
        '%-13s %s',
        $label,
        $relativePath
    );
}

function formatStatusLabel(string $status): string
{
    if ($status === 'copy') {
        return '[+] copy';
    }

    if ($status === 'exists') {
        return '[=] exists';
    }

    if ($status === 'differs') {
        return '[~] differs';
    }

    if ($status === 'obsolete') {
        return '[-] obsolete';
    }

    return '[?] unknown';
}

function buildSummaryLines(array $items): array
{
    $stats = [
        'copy' => 0,
        'exists' => 0,
        'differs' => 0,
        'obsolete' => 0,
    ];

    foreach ($items as $item) {
        $status = $item['status'];

        if (!isset($stats[$status])) {
            continue;
        }

        $stats[$status]++;
    }

    $lines = [];

    $lines[] = '';
    $lines[] = 'Summary';

    foreach ($stats as $status => $count) {
        $line = sprintf(
            '%-10s: %d',
            $status,
            $count
        );

        $lines[] = $line;
    }

    return $lines;
}

function relativePath(
    string $root,
    string $path
): string {
    $relativePath = str_replace(
        $root,
        '',
        $path
    );

    $relativePath = ltrim(
        $relativePath,
        '/'
    );

    return $relativePath;
}
