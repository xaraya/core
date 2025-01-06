# Xaraya Core Traits

This contains core utility traits that can be used in your PHP classes:

- [Cache Trait](#cache-trait)
- [Timer Trait](#timer-trait)
- [Database Trait](#database-trait)
- [Context Trait](#context-trait)
- [Module Traits](#module-traits)

## Cache Trait

Trait to cache variables in other classes

Usage:
```
use Xaraya\Caching\CacheInterface;
use Xaraya\Caching\CacheTrait;

class myFancyClass implements CacheInterface
{
    use CacheTrait;  // activate with self::$enableCache = true

    public function __construct()
    {
        // ...
        static::$enableCache = true;
        static::setCacheScope('myFancyItems');
    }

    public function getItemCached($id)
    {
        // ... get item from cache ...
        $cacheKey = static::getCacheKey($id);
        if (!empty($cacheKey) && static::isCached($cacheKey)) {
            return static::getCached($cacheKey);
        }

        // ... retrieve item here in myFancyClass ...
        $item = $this->getItem($id);

        // ... set item in cache ...
        // if you don't know the $cacheKey for item from before (e.g. because it was defined with $id elsewhere)
        // if (static::$enableCache && static::hasCacheKey()) {
        //     $cacheKey = self::getCacheKey();
        // }
        if (!empty($cacheKey)) {
            static::setCached($cacheKey, $item);
        }
        return $item;
    }
}
```

## Timer Trait

Trait to trace time and record steps taken

Usage:
```
use Xaraya\Tools\TimerInterface;
use Xaraya\Tools\TimerTrait;

class myFancyClass implements TimerInterface
{
    use TimerTrait;  // activate with self::$enableTimer = true

    public function __construct()
    {
        self::$enableTimer = true;
        // ...
        self::setTimer('contructed');
    }

    public function getResultWithTimer($what)
    {
        // ... get result with timer ...
        self::setTimer('start result');
        // some lengthy operation(s) in myFancyClass
        $result = $this->getResult($what);
        self::setTimer('stop result');

        // ... add timer information to result ...
        if (self::$enableTimer) {
            $result['timer'] = self::getTimers();
        }
        return $result;
    }
}
```

## Database Trait

Trait to handle module- or object-specific database connections.
See https://github.com/xaraya-modules/library module for an example connecting to sqlite3 databases

In modules, you can specify the database(s) by setting module vars:
```
$module = 'library';
$databases = [
    'test' => [
        'name' => 'test',
        'description' => 'Test Database',
        'databaseType' => 'sqlite3',
        'databaseName' => 'code/modules/.../xardata/test.db',
        // ...other DB params for mysql/mariadb
    ],
];
xarModVars::set($module, 'databases', serialize($databases));
xarModVars::set($module, 'dbName', 'test');
```

In DD objects, you can specify the DB connection args by setting config: see [Dynamic Data Objects README](../../../code/modules/dynamicdata/README.md#database-connections) for details
```
use Xaraya\Modules\Library\UserApi;

$config = [
    'dbConnIndex' => 1,
    'dbConnArgs' => json_encode([UserApi::class, 'getDbConnArgs'])
];
$descriptor->set('config', serialize($config));
```

If you support more than 1 database (besides the Xaraya DB), you can set the current DB for the user with:
```
$userapi = xarMod::getAPI('library');
$userapi->setCurrentDatabase($name)
```

Usage:
```
namespace Xaraya\Modules\Library;

use Xaraya\Database\DatabaseInterface;
use Xaraya\Database\DatabaseTrait;
use sys;

sys::import('xaraya.database.databasetrait');

class UserApi implements DatabaseInterface
{
    use DatabaseTrait;
}
```

## Context Trait

Trait to add context in other classes

Usage:
```
use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;

class myFancyClass implements ContextInterface
{
    use ContextTrait;

    public function doSomething()
    {
        // ... get current context ...
        $context = $this->getContext();

        // ... update current context ...
        $this->setContext($context);
    }
}
```

## Module Traits

Trait to get module classes via xarMod::getModule(), and associated traits for user/admin api/gui classes.

Usage:
```
// class/module.php
namespace Xaraya\Modules\MyFancyModule;

use Xaraya\Modules\ModuleInterface;
use Xaraya\Modules\ModuleTrait;

class Module implements ModuleInterface
{
    use ModuleTrait;
}

// class/userapi.php
namespace Xaraya\Modules\MyFancyModule;

use Xaraya\Modules\UserApiInterface;
use Xaraya\Modules\UserApiTrait;

class UserApi implements UserApiInterface
{
    use UserApiTrait;

    public function get($args = []) {
        // get single module item
        return $args;
    }
}

// class/usergui.php
namespace Xaraya\Modules\MyFancyModule;

use Xaraya\Modules\UserGuiInterface;
use Xaraya\Modules\UserGuiTrait;

class UserGui implements UserGuiInterface
{
    use UserGuiTrait;

    public function main($args = []) {
        // get main module overview
        return $args;
    }
}

// xaruser/main.php or xaruser.php
function myfancymodule_user_main($args = [], $context = null) {
    // get module class instance first
    //$module = xarMod::getModule('myfancymodule');
    //$module->setContext($context);
    //return $module->getGUI()->main($args);
    // or get module gui directly
    $usergui = xarMod::getGUI('myfancymodule');
    $usergui->setContext($context);
    return $usergui->main($args);
}

// xaruserapi/get.php or xaruserapi.php
function myfancymodule_userapi_get($args = [], $context = null) {
    // get module class instance first
    //$module = xarMod::getModule('myfancymodule');
    //$module->setContext($context);
    //return $module->getAPI()->get($args);
    // or get module api directly
    $userapi = xarMod::getAPI('myfancymodule');
    $userapi->setContext($context);
    return $userapi->get($args);
}
```

Enjoy :-)
