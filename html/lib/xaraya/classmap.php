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
 * based on file paths - @todo
 *
 * Note: this doesn't know (nor care) whether a module is active or
 * not - it simply reflects the current state of composer autoload.
 * The calling methods should take that into account if necessary.
 */
class xarClassMap extends xarObject
{
    /** @var array<string, string> */
    protected static array $classmap = [];

    /**
     * Summary of getClassMap
     * @return array<string, string>
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
     * @return array<string, string>
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
        if (!file_exists($file)) {
            return ['' => $file];
        }
        $defined = require $file;
        // we are only looking for xaraya code here, not lib, themes etc.
        return array_filter($defined, function ($path) {
            return str_contains($path, '/html/code/') || str_contains($path, '/vendor/xaraya/');
        });
    }

    /**
     * Summary of getBlocks
     * @return array<string, string>
     */
    public static function getBlocks(): array
    {
        return array_filter(static::getClassMap(), function ($path) {
            return (str_contains($path, '/html/code/modules/') && str_contains($path, '/xarblocks/'))
                || (str_contains($path, '/vendor/xaraya/') && str_contains($path, '/xarblocks/'))
                || str_contains($path, '/html/code/blocks/');
        });
    }

    /**
     * Summary of findBlock
     * @param array<string> $paths
     * @return array{filepath: string, found: array<string, string>}
     * @see xarBlock::getObject()
     */
    public static function findBlock(array $paths): array|null
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
     * Summary of findModuleClassType
     * @param string $module
     * @param string $type class type like hookobservers, eventsubjects etc.
     * @param string $filename
     * @return array<string, string>
     */
    public static function findModuleClassType($module, $type, $filename = '')
    {
        $find = "{$module}/class/{$type}/{$filename}";
        return array_filter(static::getClassMap(), function ($path) use ($find) {
            return str_contains($path, '/html/code/modules/' . $find)
                || str_contains($path, '/vendor/xaraya/' . $find);
        });
    }

    /**
     * Summary of getEventObservers
     * @return array<string, string>
     */
    public static function getEventObservers(): array
    {
        return array_filter(static::getClassMap(), function ($path) {
            return (str_contains($path, '/html/code/modules/') && str_contains($path, '/class/eventobservers/'))
                || (str_contains($path, '/vendor/xaraya/') && str_contains($path, '/class/eventobservers/'));
        });
    }

    /**
     * Summary of getHookObservers
     * @return array<string, string>
     */
    public static function getHookObservers(): array
    {
        return array_filter(static::getClassMap(), function ($path) {
            return (str_contains($path, '/html/code/modules/') && str_contains($path, '/class/hookobservers/'))
                || (str_contains($path, '/vendor/xaraya/') && str_contains($path, '/class/hookobservers/'));
        });
    }

    /**
     * Summary of findHookObserver
     * @param string $module
     * @param string $event
     * @throws \ClassNotFoundException
     * @return string|null
     * @see xarEvents::fileLoad()
     */
    public static function findHookObserver($module, $event)
    {
        $type = 'hookobservers';
        $filename = strtolower($event) . '.php';
        $found = static::findModuleClassType($module, $type, $filename);
        if (count($found) == 1) {
            $filepath = reset($found);
            $classname = array_key_first($found);
            return $classname;
        }
        if (count($found) > 1) {
            throw new ClassNotFoundException('Several hook observer classes match ' . $module . ' ' . $event);
        }
        return null;
    }

    /**
     * Summary of getProperties
     * @return array<string, string>
     */
    public static function getProperties(): array
    {
        return array_filter(static::getClassMap(), function ($path) {
            return (str_contains($path, '/html/code/modules/') && str_contains($path, '/xarproperties/'))
                || (str_contains($path, '/vendor/xaraya/') && str_contains($path, '/xarproperties/'))
                || str_contains($path, '/html/code/properties/');
        });
    }
}
