<?php
/**
 * Handle Module requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use xarMod;

/**
 * For documentation purposes only - available via ModuleBridgeTrait
 */
interface ModuleBridgeInterface extends CommonRequestInterface
{
    public static function parseModulePath(string $path = '/', array $query = [], string $prefix = ''): array;
    public static function buildModulePath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string;
    public static function runModuleGuiRequest($vars, $query): string;
    public static function runModuleApiRequest($vars, $query): mixed;
}

trait ModuleBridgeTrait
{
    public static function parseModulePath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && strpos($path, $prefix . '/') === 0) {
            $pieces = explode('/', substr($path, strlen($prefix) + 1));
            // {prefix}/{module} = user main
            $params['module'] = $pieces[0];
            if ($params['module'] == 'object') {
                // see DataObjectBridgeTrait with prefix /object
                return static::parseDataObjectPath($path, $query, $prefix . '/object');
            }
            if (count($pieces) == 2) {
                // {prefix}/{module}/{func} = user view, display, ...
                $params['type'] = 'user';
                $params['func'] = $pieces[1];
            } elseif (count($pieces) > 2) {
                // {prefix}/{module}/{type}/{func} = admin main, new, config, ...
                $params['type'] = $pieces[1];
                $params['func'] = $pieces[2];
            }
        }
        // add remaining query params to path params
        $params = array_merge($params, $query);
        return $params;
    }

    public static function buildModulePath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string
    {
        if ($module == 'object') {
            $itemid = $extra['itemid'] ?? null;
            unset($extra['itemid']);
            // see DataObjectBridgeTrait with prefix /object
            return static::buildDataObjectPath($type, $func, $itemid, $extra, '/object');
        }
        // see xarServer::getModuleURL()
        $uri = static::$baseUri;
        if (!empty($prefix) && strstr($uri, $prefix) !== $prefix) {
            $uri .= $prefix;
        }
        // {prefix}/{module} = user main
        $uri .= '/' . $module;
        if (empty($type) || $type == 'user') {
            if (!empty($func) && $func != 'main') {
                // {prefix}/{module}/{func} = user view, display, ...
                $uri .= '/' . $func;
            }
        } else {
            $uri .= '/' . $type;
            if (empty($func)) {
                $func = 'main';
            }
            // {prefix}/{module}/{type}/{func} = admin main, new, config, ...
            $uri .= '/' . $func;
        }
        if (!empty($extra)) {
            $uri .= '?' . http_build_query($extra);
        }
        return $uri;
    }

    public static function runModuleGuiRequest($vars, $query): string
    {
        return xarMod::guiFunc($vars['module'], $vars['type'] ?? 'user', $vars['func'] ?? 'main', $query);
    }

    public static function runModuleApiRequest($vars, $query): mixed
    {
        return xarMod::apiFunc($vars['module'], $vars['type'] ?? 'user', $vars['func'] ?? 'getitemtypes', $query);
    }
}
