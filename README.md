# xaraya/core project

This provides the Xaraya core framework with the essential modules, blocks, properties and themes.

Optional [xaraya/modules](https://github.com/mikespub/xaraya-modules) and [xaraya/properties](https://github.com/mikespub/xaraya-properties) bundles will be installed in development mode.

## Requirements

- PHP 8.2+ with mbstring, XML and XSL extensions
- MariaDB 10.x or MySQL 8.x (or SQLite 3.x in tests)
- composer 2.x for installation

## Installation

Create Xaraya core project using `composer` in current directory or "myproject" subdirectory ([composer create-project](https://getcomposer.org/doc/03-cli.md#create-project))

```sh
composer create-project xaraya/core [myproject]
```

## Xaraya Modules

Add other [Xaraya Modules](https://github.com/xaraya-modules) as `composer` packages to your project ([composer require](https://getcomposer.org/doc/03-cli.md#require-r))

```sh
composer require xaraya/library
```
