<?php
/**
 * Separate trait of common utilities for different bridge types
 */

namespace Xaraya\Bridge;

// use some Xaraya classes
use xarController;
use xarServer;
use xarSystemVars;
use xarMod;
use sys;

/**
 * For documentation purposes only - available via CommonRequestTrait
 * Accepts PSR-7 compatible server requests, xarRequest (partial use) or nothing (using $_SERVER)
 */
interface CommonRequestInterface
{
    public static function getMethod($request = null): string;
    public static function getPathInfo($request = null): string;
    public static function getBaseUri($request = null): string;
    public static function getQueryParams($request = null): array;
    public static function getServerParams($request = null): array;
}

/**
 * For documentation purposes only - available via CommonBridgeTrait
 */
interface CommonBridgeInterface extends CommonRequestInterface
{
    public static function parseModulePath(string $path = '/', array $query = [], string $prefix = ''): array;
    public static function parseDataObjectPath(string $path = '/', array $query = [], string $prefix = ''): array;
    public static function prepareController(string $module = 'base', string $baseUri = ''): void;
    public static function buildModulePath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string;
    public static function buildDataObjectPath(string $object = 'sample', ?string $method = null, string|int|null $itemid = null, array $extra = [], string $prefix = '/object'): string;
}

/**
 * Accepts PSR-7 compatible server requests, xarRequest (partial use) or nothing (using $_SERVER)
 */
trait CommonRequestTrait
{
    public static function getMethod($request = null): string
    {
        // for PSR-7 compatible requests and xarRequest
        if (is_object($request) && method_exists($request, 'getMethod')) {
            return $request->getMethod();
        }
        // for everyone else
        $server = static::getServerParams($request);
        return $server['REQUEST_METHOD'] ?? 'GET';
    }

    public static function getPathInfo($request = null): string
    {
        // for PSR-7 compatible server requests and everyone else
        $server = static::getServerParams($request);
        return $server['PATH_INFO'] ?? '';
    }

    public static function getBaseUri($request = null): string
    {
        // for PSR-7 compatible server requests and everyone else
        $server = static::getServerParams($request);
        $requestPath = explode('?', $server['REQUEST_URI'] ?? '')[0];
        // {request_uri} = {/baseurl/script.php}{/path_info}?{query_string}
        if (!empty($server['SCRIPT_NAME']) && strpos($requestPath, $server['SCRIPT_NAME']) === 0) {
            return $server['SCRIPT_NAME'];
        }
        // {request_uri} = {/otherurl}{/path_info}?{query_string} = mod_rewrite possibly unrelated to {/baseurl/script.php}
        if (!empty($server['PATH_INFO']) && strpos($requestPath, $server['PATH_INFO']) !== false) {
            return substr($requestPath, 0, strlen($requestPath) - strlen($server['PATH_INFO']));
        }
        // {request_uri} = {/otherurl}?{query_string} = mod_rewrite possibly unrelated to {/baseurl/script.php}
        return $requestPath;
    }

    public static function getQueryParams($request = null): array
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getQueryParams')) {
            return $request->getQueryParams();
        }
        // for everyone else
        $server = static::getServerParams($request);
        $query = [];
        if (!empty($server['QUERY_STRING'])) {
            parse_str($server['QUERY_STRING'], $query);
        }
        return $query;
    }

    public static function getServerParams($request = null): array
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getServerParams')) {
            return $request->getServerParams();
        }
        // for everyone else
        return $_SERVER;
    }
}

trait CommonBridgeTrait
{
    use CommonRequestTrait;

    public static function parseModulePath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && strpos($path, $prefix . '/') === 0) {
            $pieces = explode('/', substr($path, strlen($prefix) + 1));
            // {prefix}/{module} = user main
            $params['module'] = $pieces[0];
            if ($params['module'] == 'object') {
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

    public static function parseDataObjectPath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && strpos($path, $prefix . '/') === 0) {
            $pieces = explode('/', substr($path, strlen($prefix) + 1));
            // {prefix}/{object} = view
            $params['object'] = $pieces[0];
            if (count($pieces) > 1) {
                if (!is_numeric($pieces[1])) {
                    // {prefix}/{object}/{method} = new, query, stats, ...
                    $params['method'] = $pieces[1];
                } else {
                    // {prefix}/{object}/{itemid} = display
                    $params['itemid'] = $pieces[1];
                    if (count($pieces) > 2) {
                        // {prefix}/{object}/{itemid}/{$method} = update, delete, ...
                        $params['method'] = $pieces[2];
                    }
                }
            }
        }
        // add remaining query params to path params
        $params = array_merge($params, $query);
        return $params;
    }

    public static function prepareController(string $module = 'base', string $baseUri = ''): void
    {
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        xarController::getRequest()->setModule($module);
        // @checkme override system config here, since xarController does re-init() for each URL() for some reason...
        $entryPoint = str_replace(xarServer::getBaseURI(), '', $baseUri);
        //xarSystemVars::set(sys::LAYOUT, 'BaseURI');
        xarSystemVars::set(sys::LAYOUT, 'BaseModURL', $entryPoint);
        xarController::$entryPoint = $entryPoint;
        // @checkme set buildUri for any other links to the ModuleRouter here
        //xarController::$buildUri = [static::class, 'buildUri'];
        //sys::import('modules.modules.controllers.router');
        //ModuleRouter::setBaseUri($baseUri);
        xarController::$buildUri = [static::class, 'buildModulePath'];
    }

    public static function buildModulePath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string
    {
        if ($module == 'object') {
            $itemid = $extra['itemid'] ?? null;
            unset($extra['itemid']);
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

    public static function buildDataObjectPath(string $object = 'sample', ?string $method = null, string|int|null $itemid = null, array $extra = [], string $prefix = '/object'): string
    {
        // see xarDDObject::getObjectURL() and xarServer::getObjectURL()
        $uri = static::$baseUri;
        if (!empty($prefix) && strstr($uri, $prefix) !== $prefix) {
            $uri .= $prefix;
        }
        // {prefix}/{object} = view
        $uri .= '/' . $object;
        if (empty($itemid)) {
            if (!empty($method) && $method != 'view') {
                // {prefix}/{object}/{method} = new, query, stats, ...
                $uri .= '/' . $method;
            }
        } else {
            // {prefix}/{object}/{itemid} = display
            $uri .= '/' . $itemid;
            if (!empty($method) && $method != 'display') {
                // {prefix}/{object}/{itemid}/{$method} = update, delete, ...
                $uri .= '/' . $method;
            }
        }
        if (!empty($extra)) {
            $uri .= '?' . http_build_query($extra);
        }
        return $uri;
    }
}
