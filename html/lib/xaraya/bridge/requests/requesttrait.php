<?php
/**
 * Handle generic requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * Accepts PSR-7 compatible server requests, xarRequest (partial use) or nothing (using $_SERVER)
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use sys;

sys::import('modules.dynamicdata.class.userinterface');

/**
 * For documentation purposes only - available via CommonRequestTrait
 */
interface CommonRequestInterface
{
    public static function getMethod($request = null): string;
    public static function getPathInfo($request = null): string;
    public static function getBaseUri($request = null): string;
    public static function getQueryParams($request = null): array;
    public static function getServerParams($request = null): array;
}

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
