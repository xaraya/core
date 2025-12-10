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

## Legacy Support

Older 2.x modules can be supported under the following conditions:
- **Removed**: no use of legacy 1.x core functions, e.g. `xarUserGetVar` to `xarUser::getVar` (2.4.5)
- **Renamed**: replace static method calls for `DataObjectMaster::get*` with `DataObjectFactory::get*` (2.7.3)
- **Autoload**: remove/rename conflicting classes, e.g. left-over copies of `class/hooksubjects/*` (2.8.0)

After copying the module files to the `html/code/modules/[mymodule]/` directory, be sure to run dump-autoload again:
```sh
composer dump-autoload -o
```

You can use developer tools `bermuda_cleanup.php` to clean up 2.x module code and templates.
Older 1.x modules can be converted using `aruba2jamaica-2.1.php` first.
Some "minor" clean-up will be needed afterwards :-)
