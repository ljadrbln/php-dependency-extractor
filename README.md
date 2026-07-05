# PHP Dependency Extractor

Extract all reachable PHP project files starting from one or more entry points.

The tool analyzes PHP source code, builds a dependency graph, and copies only the files required by the selected feature.

## Features

- Dependency graph analysis
- Dry-run by default
- Optional file synchronization
- Multiple entry points
- No external PHP parser required
- No vendor scanning

## Requirements

- PHP 8.3+

or

- Docker

## Example

```bash
php php-dependency-extractor.php \
    --source=/path/to/source \
    --target=/path/to/target \
    --entry=backend/src/Configuration/Providers/User/SignUpProvider.php
```

Apply changes:

```bash
php php-dependency-extractor.php \
    --source=/path/to/source \
    --target=/path/to/target \
    --entry=backend/src/Configuration/Providers/User/SignUpProvider.php \
    --apply
```

## Status

The project is under active development.
