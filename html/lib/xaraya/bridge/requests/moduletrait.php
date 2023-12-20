<?php
/**
 * Handle Module requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Xaraya\Context\Context;
use xarMod;

/**
 * For documentation purposes only - available via ModuleBridgeTrait
 */
interface ModuleBridgeInterface extends CommonRequestInterface
{
    /**
     * Summary of parseModulePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public static function parseModulePath(string $path = '/', array $query = [], string $prefix = ''): array;

    /**
     * Summary of buildModulePath
     * @param string $module
     * @param ?string $type
     * @param string|int|null $func
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildModulePath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string;

    /**
     * Summary of runModuleGuiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @param ?Context<string, mixed> $context
     * @return string|null
     */
    public static function runModuleGuiRequest($vars, $query, $context = null): ?string;

    /**
     * Summary of runModuleApiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @param ?Context<string, mixed> $context
     * @return mixed
     */
    public static function runModuleApiRequest($vars, $query, $context = null): mixed;
}

trait ModuleBridgeTrait
{
    /**
     * Summary of parseModulePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
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

    /**
     * Summary of buildModulePath
     * @param string $module
     * @param ?string $type
     * @param string|int|null $func
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
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

    /**
     * Summary of runModuleGuiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @param ?Context<string, mixed> $context
     * @return string|null
     */
    public static function runModuleGuiRequest($vars, $query, $context = null): ?string
    {
        return xarMod::guiFunc($vars['module'], $vars['type'] ?? 'user', $vars['func'] ?? 'main', $query, $context);
    }

    /**
     * Summary of runModuleApiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @param ?Context<string, mixed> $context
     * @return mixed
     */
    public static function runModuleApiRequest($vars, $query, $context = null): mixed
    {
        return xarMod::apiFunc($vars['module'], $vars['type'] ?? 'user', $vars['func'] ?? 'getitemtypes', $query, $context);
    }
}
