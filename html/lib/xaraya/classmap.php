<?php

/**
 * Xaraya Class Map
 *
 * @package core\classmap
 * @category Xaraya Web Applications Framework
 * @version 2.7.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/**
 * Xaraya Class Map based on composer autoload_classmap
 *
 * This is to replace the use of get_declared_classes() when
 * looking for particular blocks, observers, properties etc.
 * based on file paths - @todo adapt based on layout.system.php
 *
 * Note: this doesn't know (nor care) whether a module is active or
 * not - it simply reflects the current state of composer autoload.
 * The calling methods should take that into account if necessary.
 */
class xarClassMap extends xarObject
{
    public const PARSED_CACHE_FILE = 'classmap_parsed.php';

    /** @var array<string, array<mixed>> */
    protected static array $classmap = [];

    /**
     * Summary of getClassMap
     * @return array<string, array<mixed>>
     */
    public static function getClassMap(): array
    {
        if (empty(static::$classmap)) {
            static::$classmap = static::loadClassMap();
        }
        return static::$classmap;
    }

    /**
     * Summary of loadClassMap
     * @return array<string, array<mixed>>
     */
    public static function loadClassMap(): array
    {
        $root = sys::root();
        if (empty($root) || $root == sys::web() || !is_dir($root)) {
            $root = dirname(__DIR__, 3);
        } else {
            $root = rtrim($root, '/');
        }
        $file = $root . '/vendor/composer/autoload_classmap.php';
        $classmap = [
            'autoload' => [
                'classmap' => $file,
                'generated' => date('c'),
            ],
        ];
        if (!file_exists($file)) {
            echo 'Invalid autoload classmap: ' . $file . "\n";
            echo 'Please run `composer update` or `composer dump-autoload` first';
            return $classmap;
        }
        $cacheFile = sys::varpath() . '/cache/' . self::PARSED_CACHE_FILE;
        if (file_exists($cacheFile) && filemtime($cacheFile) > filemtime($file)) {
            // load cacheFile
            $classmap = require $cacheFile;
            return $classmap;
        }
        $parser = new \Xaraya\Tools\ClassMapParser();
        $classmap = $parser->parse($file);
        file_put_contents($cacheFile, "<?php\nreturn " . var_export($classmap, true) . ";\n");
        return $classmap;
    }

    /**
     * Summary of clearClassMap
     * @return void
     */
    public static function clearClassMap()
    {
        static::$classmap = [];
        $cacheFile = sys::varpath() . '/cache/' . self::PARSED_CACHE_FILE;
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    /**
     * Summary of getClassType
     * @return array<string, mixed>
     */
    public static function getClassType(string $classType): array
    {
        static::getClassMap();
        return static::$classmap[$classType] ?? [];
    }

    /**
     * Summary of getClassFiles
     * @param string $classType class type like modules, blocks, hookobservers, eventsubjects etc.
     * @param ?string $modName (optional)
     * @param ?string $fileType (optional)
     * @param bool $fuzzy use fuzzy match (optional) for blocks
     * @return array<string, string>
     */
    public static function getClassFiles(string $classType, ?string $modName = null, ?string $fileType = null, bool $fuzzy = false): array
    {
        // we can specify modName or fileType or both here
        $found = static::getClassType($classType);
        if (!is_null($modName)) {
            if (empty($found[$modName])) {
                return [];
            }
            $modules = [ $modName ];
        } else {
            $modules = array_keys($found);
        }
        if (!empty($fileType)) {
            $fileType = strtolower($fileType);
        }
        $result = [];
        foreach ($modules as $module) {
            foreach ($found[$module] as $type => $class) {
                if (!empty($fileType)) {
                    if (!$fuzzy && $type != $fileType) {
                        continue;
                    }
                    // @todo handle interface? This could be .php, _display.php or /admin.php - last one is not supported
                    if ($fuzzy && !str_starts_with($type, $fileType)) {
                        continue;
                    }
                }
                $result = array_merge($result, $class);
            }
        }
        return $result;
    }

    /**
     * Summary of findClassFile
     * @param string $classType
     * @param string $modName
     * @param string $fileType
     * @param ?string $suffix (optional) for middleware, dataobjects etc.
     * @throws \DuplicateException
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     * @see xarEvents::fileLoad() not used except for hook observers
     */
    public static function findClassFile(string $classType, string $modName, string $fileType, ?string $suffix = null): ?array
    {
        $found = static::getClassFiles($classType, $modName, $fileType);
        if (!empty($suffix) && count($found) > 1) {
            $found = array_filter($found, function ($class) use ($suffix) {
                return str_ends_with($class, $suffix);
            }, ARRAY_FILTER_USE_KEY);
        }
        if (count($found) == 1) {
            $filePath = reset($found);
            $className = array_key_first($found);
            return ['classname' => $className, 'filepath' => $filePath, 'classtype' => $classType, 'module' => $modName, 'filetype' => $fileType];
        }
        if (count($found) > 1) {
            $msg = 'Several "#(1)" classes match module "#(2)" type "#(3)"';
            $vars = [$classType, $modName, $fileType];
            throw new DuplicateException($vars, $msg);
        }
        return null;
    }

    /**
     * Summary of getBlocks
     * @param ?string $modName
     * @param ?string $type
     * @return array<string, string>
     */
    public static function getBlocks(?string $modName = null, ?string $type = null): array
    {
        // we can specify modName or type or both here
        // @todo handle interface? This could be .php, _display.php or /admin.php - last one is not supported
        return static::getClassFiles('blocks', $modName, $type, true);
    }

    /**
     * Summary of findBlock
     * @param string $modName use empty string for stand-alone blocks
     * @param string $type
     * @param ?string $interface (optional)
     * @throws \DuplicateException
     * @return array{classname: string, filepath: string, module: string, type: string, interface: string}|null
     */
    public static function findBlock(string $modName, string $type, ?string $interface = null): ?array
    {
        $interface ??= '';
        $found = static::getBlocks($modName, $type);
        if (count($found) > 1) {
            // filter by interface here
            $suffix = 'Block';
            if (!empty($interface)) {
                // e.g. BlockDisplay, BlockConfig, BlockAdmin, ...
                $suffix .= ucfirst($interface);
            }
            $filter = array_filter($found, function ($class) use ($suffix) {
                return str_ends_with($class, $suffix);
            }, ARRAY_FILTER_USE_KEY);
            // try again with BlockAdmin suffix for admin classes
            if (count($filter) < 1 && !empty($interface) && !in_array($interface, ['display', 'admin'])) {
                $suffix = 'BlockAdmin';
                $filter = array_filter($found, function ($class) use ($suffix) {
                    return str_ends_with($class, $suffix);
                }, ARRAY_FILTER_USE_KEY);
            }
            // try again with Block suffix for all-in-one classes
            if (count($filter) < 1 && !empty($interface)) {
                $suffix = 'Block';
                $filter = array_filter($found, function ($class) use ($suffix) {
                    return str_ends_with($class, $suffix);
                }, ARRAY_FILTER_USE_KEY);
            }
            $found = $filter;
        }
        if (count($found) == 1) {
            $filePath = reset($found);
            $className = array_key_first($found);
            return ['classname' => $className, 'filepath' => $filePath, 'module' => $modName, 'type' => $type, 'interface' => $interface];
        }
        if (count($found) > 1) {
            $msg = 'Several "#(1)" classes match module "#(2)" type "#(3)"';
            $vars = ['blocks', $modName, $type . $interface];
            throw new DuplicateException($vars, $msg);
        }
        return null;
    }

    /**
     * Summary of findBlockByPath
     * @param array<string> $paths
     * @return array{filepath: string, found: array<string, string>}
     * @see xar::block()->getObject()
     */
    public static function findBlockByPath(array $paths): ?array
    {
        // remove sys::code() from paths but keep last /
        $syscode = rtrim(sys::code(), '/');
        array_walk($paths, function (&$path) use ($syscode) {
            if (str_starts_with($path, $syscode)) {
                $path = substr($path, strlen($syscode));
            }
        });
        // find first matching path
        $found = [];
        $blocks = static::getBlocks();
        foreach ($paths as $find) {
            $found = array_filter($blocks, function ($path) use ($find) {
                return str_ends_with($path, $find);
            });
            if (count($found) > 0) {
                return ['filepath' => $syscode . $find, 'found' => $found];
            }
        }
        return ['filepath' => '', 'found' => $found];
    }

    /**
     * Summary of getEventSubjects
     * @param ?string $modName (optional)
     * @param ?string $event (optional)
     * @return array<string, string>
     */
    public static function getEventSubjects(?string $modName = null, ?string $event = null): array
    {
        // we can specify modName or event or both here
        return static::getClassFiles('eventsubjects', $modName, $event);
    }

    /**
     * Summary of getHookSubjects
     * @param ?string $modName (optional)
     * @param ?string $event (optional)
     * @return array<string, string>
     */
    public static function getHookSubjects(?string $modName = null, ?string $event = null): array
    {
        // we can specify modName or event or both here
        return static::getClassFiles('hooksubjects', $modName, $event);
    }

    /**
     * Summary of getEventObservers
     * @param ?string $modName (optional)
     * @param ?string $event (optional)
     * @return array<string, string>
     */
    public static function getEventObservers(?string $modName = null, ?string $event = null): array
    {
        // we can specify modName or event or both here
        return static::getClassFiles('eventobservers', $modName, $event);
    }

    /**
     * Summary of findEventObserver
     * @param string $modName
     * @param string $event
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     */
    public static function findEventObserver(string $modName, string $event): ?array
    {
        return static::findClassFile('eventobservers', $modName, $event);
    }

    /**
     * Summary of getHookObservers
     * @param ?string $modName (optional)
     * @param ?string $event (optional)
     * @return array<string, string>
     */
    public static function getHookObservers(?string $modName = null, ?string $event = null): array
    {
        // we can specify modName or event or both here
        return static::getClassFiles('hookobservers', $modName, $event);
    }

    /**
     * Summary of findHookObserver
     * @param string $modName
     * @param string $event
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     * @see xarEvents::fileLoad()
     */
    public static function findHookObserver(string $modName, string $event): ?array
    {
        return static::findClassFile('hookobservers', $modName, $event);
    }

    /**
     * Summary of getDataObjects - @todo not reliable here
     * @param ?string $modName (optional)
     * @param ?string $type dataobject type (optional)
     * @return array<string, string>
     */
    public static function getDataObjects(?string $modName = null, ?string $type = null): array
    {
        // we can specify modName or type or both here
        return static::getClassFiles('dataobjects', $modName, $type);
    }

    /**
     * Summary of findDataObject - @todo not reliable here
     * @param string $modName
     * @param string $type dataobject type like role, category etc.
     * @param ?string $suffix (optional) for middleware, dataobjects etc.
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     */
    public static function findDataObject(string $modName, string $type, ?string $suffix = null)
    {
        // we have 2 classes here: Role and RoleList - pick one based on $suffix
        return static::findClassFile('dataobjects', $modName, $type, $suffix);
    }

    /**
     * Summary of getProperties
     * @param ?string $modName (optional)
     * @param ?string $type property type (optional)
     * @return array<string, string>
     * @see PropertyRegistration::importPropertyTypes()
     */
    public static function getProperties(?string $modName = null, ?string $type = null): array
    {
        // we can specify modName or type or both here
        return static::getClassFiles('properties', $modName, $type);
    }

    /**
     * Summary of findProperty
     * @param string $modName use empty string for stand-alone properties
     * @param string $type property type
     * @throws \DuplicateException
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     */
    public static function findProperty(string $modName, string $type): ?array
    {
        // Ignore installer classes of properties (they are extensions)
        $suffix = 'Property';
        return static::findClassFile('properties', $modName, $type, $suffix);
    }

    /**
     * Summary of getControllers
     * @param ?string $modName (optional)
     * @param ?string $type route type like default, short etc. (optional)
     * @return array<string, string>
     */
    public static function getControllers(?string $modName = null, ?string $type = null): array
    {
        // we can specify modName or type or both here
        return static::getClassFiles('controllers', $modName, $type);
    }

    /**
     * Summary of findController
     * @param string $modName
     * @param string $type route type like default, short etc.
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     * @see xarDispatcher::findController()
     */
    public static function findController(string $modName, string $type)
    {
        return static::findClassFile('controllers', $modName, $type);
    }

    /**
     * Summary of getMiddleware
     * @param ?string $modName (optional)
     * @param ?string $type middleware type like router, middleware etc. (optional)
     * @return array<string, string>
     */
    public static function getMiddleware(?string $modName = null, ?string $type = null): array
    {
        // we can specify modName or type or both here
        return static::getClassFiles('middleware', $modName, $type);
    }

    /**
     * Summary of findMiddleware
     * @param string $modName
     * @param string $type middleware type like router, middleware etc.
     * @param ?string $suffix (optional) for middleware, dataobjects etc.
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     */
    public static function findMiddleware(string $modName, string $type, ?string $suffix = null)
    {
        // we have 2 classes here: DataObjectMiddleware and DataObjectApiMiddleware - pick one based on $suffix
        return static::findClassFile('middleware', $modName, $type, $suffix);
    }

    /**
     * Summary of getHandlers
     * @param ?string $modName (optional)
     * @param ?string $type handler type (optional) - not used
     * @return array<string, string>
     */
    public static function getHandlers(?string $modName = null, ?string $type = null): array
    {
        // we can specify modName or type or both here
        return static::getClassFiles('handlers', $modName, $type);
    }

    /**
     * Summary of findHandler
     * @param string $modName
     * @param ?string $type handler type (optional) - not used
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     */
    public static function findHandler(string $modName, ?string $type = null)
    {
        // we only have 1 handler class per module for the moment
        $type ??= '';
        return static::findClassFile('handlers', $modName, $type);
    }

    /**
     * Summary of getRoutes
     * @param ?string $modName (optional)
     * @param ?string $type routes type (optional) - not used
     * @return array<string, string>
     */
    public static function getRoutes(?string $modName = null, ?string $type = null): array
    {
        // we can specify modName or type or both here
        return static::getClassFiles('routes', $modName, $type);
    }

    /**
     * Summary of findRoutes
     * @param string $modName
     * @param ?string $type routes type (optional) - not used
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     */
    public static function findRoutes(string $modName, ?string $type = null)
    {
        // we only have 1 routes class per module for the moment
        $type ??= '';
        return static::findClassFile('routes', $modName, $type);
    }

    /**
     * Summary of getTables
     * @param ?string $modName (optional)
     * @return array<string, string>
     */
    public static function getTables(?string $modName = null): array
    {
        // we can specify modName here
        return static::getClassFiles('tables', $modName);
    }

    /**
     * Summary of findTables
     * @param string $modName
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     */
    public static function findTables(string $modName)
    {
        // we only have 1 tables class per module for the moment
        $type = 'tables';
        return static::findClassFile('tables', $modName, $type);
    }

    /**
     * Summary of getVersions
     * @param ?string $modName (optional)
     * @return array<string, string>
     */
    public static function getVersions(?string $modName = null): array
    {
        // we can specify modName here
        return static::getClassFiles('versions', $modName);
    }

    /**
     * Summary of findVersion
     * @param string $modName
     * @return array{classname: string, filepath: string, classtype: string, module: string, filetype: string}|null
     */
    public static function findVersion(string $modName)
    {
        // we only have 1 version class per module for the moment
        $type = 'version';
        return static::findClassFile('versions', $modName, $type);
    }

    /**
     * Summary of getModules
     * @param ?string $modName (optional)
     * @return array<string, array{classname: string, filepath: string, module: string}>
     */
    public static function getModules(?string $modName = null): array
    {
        $classType = 'modules';
        $fileType = 'module';
        $found = static::getClassFiles($classType, $modName, $fileType);
        $modules = [];
        foreach ($found as $className => $filePath) {
            $modName = basename(dirname($filePath));
            $modules[$modName] = ['classname' => $className, 'filepath' => $filePath, 'module' => $modName];
        }
        return $modules;
    }

    /**
     * Summary of findModule
     * @param string $modName
     * @return array{classname: string, filepath: string, module: string}|null
     * @see \xar::mod()->getModule()
     */
    public static function findModule(string $modName): ?array
    {
        $modules = static::getModules($modName);
        return $modules[$modName] ?? null;
    }

    /**
     * Summary of getModuleClasses
     * @param string $modName
     * @param ?string $modType (optional)
     * @return array<string, array{classname: string, filepath: string, module: string, classtype: string}>
     */
    public static function getModuleClasses(string $modName, ?string $modType = null): array
    {
        $classType = 'modules';
        $found = static::getClassFiles($classType, $modName, $modType);
        $classTypes = [];
        foreach ($found as $className => $filePath) {
            $modType = str_replace('.php', '', basename($filePath));
            if ($modType == 'module') {
                continue;
            }
            $parts = explode('\\', $className);
            $classType = end($parts);
            $classTypes[$modType] = ['classname' => $className, 'filepath' => $filePath, 'module' => $modName, 'classtype' => $classType];
        }
        return $classTypes;
    }

    /**
     * Summary of findModuleClass
     * @param string $modName
     * @param string $modType
     * @return array{classname: string, filepath: string, module: string, classtype: string}|null
     * @see \Xaraya\Modules\ModuleTrait::getClassName()
     */
    public static function findModuleClass(string $modName, string $modType): ?array
    {
        $modType = strtolower($modType);
        $classTypes = static::getModuleClasses($modName, $modType);
        return $classTypes[$modType] ?? null;
    }

    /**
     * Summary of getModuleClassMethods
     * @todo this excludes any methods inside the module class itself
     * @param string $modName
     * @param string $modType
     * @param ?string $funcName (optional)
     * @return array<string, array{classname: string, filepath: string, method: string}>
     */
    public static function getModuleClassMethods(string $modName, string $modType, ?string $funcName = null): array
    {
        $classType = 'methods';
        $methods = static::getClassType($classType);
        if (empty($methods[$modName])) {
            return [];
        }
        $modType = strtolower($modType);
        if (empty($methods[$modName][$modType])) {
            return [];
        }
        $filename = '';
        if (!empty($funcName)) {
            // @todo support converted method name here too?
            $filename = strtolower($funcName) . '.php';
            //$methodName = str_replace('_', '', ucwords($funcName, '_')) . 'Method';
        }
        $result = [];
        foreach ($methods[$modName][$modType] as $fileType => $class) {
            $filePath = reset($class);
            $className = array_key_first($class);
            if (!empty($filename) && basename($filePath) != $filename) {
                continue;
            }
            $funcName = str_replace('.php', '', basename($filePath));
            $result[$funcName] = ['classname' => $className, 'filepath' => $filePath, 'method' => $funcName];
        }
        return $result;
    }

    /**
     * Summary of findModuleClassMethod
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @return array{classname: string, filepath: string, method: string}|null
     * @see \Xaraya\Modules\ModuleClassTrait::getClassName()
     */
    public static function findModuleClassMethod(string $modName, string $modType, string $funcName): ?array
    {
        $modType = strtolower($modType);
        // @todo support converted method name here too?
        $funcName = strtolower($funcName);
        //$methodName = str_replace('_', '', ucwords($funcName, '_')) . 'Method';
        $methods = static::getModuleClassMethods($modName, $modType, $funcName);
        return $methods[$funcName] ?? null;
    }
}
