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
    public static function getCookieParams($request = null): array;
    public static function getAuthToken($request = null): string;
    public static function getUploadedFiles($request = null): array;
    public static function getParsedBody($request = null): mixed;
    public static function getJsonBody($request = null): mixed;
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
        // no PATH_INFO available for ReactPHP etc.
        if (is_object($request) && method_exists($request, 'getUri') && empty($server['PATH_INFO'])) {
            return $request->getUri()->getPath();
        }
        return $server['PATH_INFO'] ?? '';
    }

    public static function getBaseUri($request = null): string
    {
        // for PSR-7 compatible requests
        if (is_object($request) && method_exists($request, 'getAttribute')) {
            // did we already filter out the base uri in router middleware?
            if ($request->getAttribute('baseUri') !== null) {
                return $request->getAttribute('baseUri');
            }
        }
        // for PSR-7 compatible server requests and everyone else
        $server = static::getServerParams($request);
        $requestPath = explode('?', $server['REQUEST_URI'] ?? '')[0];
        // no REQUEST_URI, SCRIPT_NAME or PATH_INFO available for ReactPHP etc.
        if (empty($requestPath)) {
            return $requestPath;
        }
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

    public static function getCookieParams($request = null): array
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getCookieParams')) {
            return $request->getCookieParams();
        }
        // for everyone else
        return $_COOKIE;
    }

    public static function getAuthToken($request = null): string
    {
        // for PSR-7 compatible requests
        if (is_object($request) && method_exists($request, 'hasHeader')) {
            if ($request->hasHeader('X-Auth-Token')) {
                return $request->getHeaderLine('X-Auth-Token');
            }
        }
        // for PSR-7 compatible server requests and everyone else
        $server = static::getServerParams($request);
        if (empty($server) || empty($server['HTTP_X_AUTH_TOKEN'])) {
            return '';
        }
        return $server['HTTP_X_AUTH_TOKEN'];
    }

    public static function getUploadedFiles($request = null): array
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getUploadedFiles')) {
            return $request->getUploadedFiles();
        }
        // for everyone else
        return $_FILES;
    }

    public static function getParsedBody($request = null): mixed
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getParsedBody')) {
            return $request->getParsedBody();
        }
        // for everyone else
        return $_POST;
    }

    public static function getJsonBody($request = null): mixed
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getBody')) {
            $rawInput = (string) $request->getBody();
        } else {
            // for everyone else
            $rawInput = file_get_contents('php://input');
        }
        $input = null;
        if (!empty($rawInput)) {
            $input = json_decode($rawInput, true, 512, JSON_THROW_ON_ERROR);
        }
        return $input;
    }
}
