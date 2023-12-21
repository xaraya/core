<?php
/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
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
    /**
     * Summary of getMethod
     * @param mixed $request
     * @return string
     */
    public static function getMethod($request = null): string;

    /**
     * Summary of getPathInfo
     * @param mixed $request
     * @return string
     */
    public static function getPathInfo($request = null): string;

    /**
     * Summary of getBaseUri
     * @param mixed $request
     * @return string
     */
    public static function getBaseUri($request = null): string;

    /**
     * Summary of getQueryParams
     * @param mixed $request
     * @return array<string, mixed>
     */
    public static function getQueryParams($request = null): array;

    /**
     * Summary of getServerParams
     * @param mixed $request
     * @return array<string, mixed>
     */
    public static function getServerParams($request = null): array;

    /**
     * Summary of getCookieParams
     * @param mixed $request
     * @return array<string, mixed>
     */
    public static function getCookieParams($request = null): array;

    /**
     * Summary of getAuthToken
     * @param mixed $request
     * @return string
     */
    public static function getAuthToken($request = null): string;

    /**
     * Summary of getUploadedFiles
     * @param mixed $request
     * @return array<string, mixed>
     */
    public static function getUploadedFiles($request = null): array;

    /**
     * Summary of getParsedBody
     * @param mixed $request
     * @return mixed
     */
    public static function getParsedBody($request = null): mixed;

    /**
     * Summary of getJsonBody
     * @param mixed $request
     * @return mixed
     */
    public static function getJsonBody($request = null): mixed;
}

/**
 * Handle generic requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * Accepts PSR-7 compatible server requests, xarRequest (partial use) or nothing (using $_SERVER)
 */
trait CommonRequestTrait
{
    /**
     * Summary of getMethod
     * @param mixed $request
     * @return string
     */
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

    /**
     * Summary of getPathInfo
     * @param mixed $request
     * @return string
     */
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

    /**
     * Summary of getBaseUri
     * @param mixed $request
     * @return string
     */
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

    /**
     * Summary of getQueryParams
     * @param mixed $request
     * @return array<string, mixed>
     */
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

    /**
     * Summary of getServerParams
     * @param mixed $request
     * @return array<string, mixed>
     */
    public static function getServerParams($request = null): array
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getServerParams')) {
            return $request->getServerParams();
        }
        // for everyone else
        return $_SERVER;
    }

    /**
     * Summary of getCookieParams
     * @param mixed $request
     * @return array<string, mixed>
     */
    public static function getCookieParams($request = null): array
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getCookieParams')) {
            return $request->getCookieParams();
        }
        // for everyone else
        return $_COOKIE;
    }

    /**
     * Summary of getAuthToken
     * @param mixed $request
     * @return string
     */
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

    /**
     * Summary of getUploadedFiles
     * @param mixed $request
     * @return array<string, mixed>
     */
    public static function getUploadedFiles($request = null): array
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getUploadedFiles')) {
            return $request->getUploadedFiles();
        }
        // for everyone else
        return $_FILES;
    }

    /**
     * Summary of getParsedBody
     * @param mixed $request
     * @return mixed
     */
    public static function getParsedBody($request = null): mixed
    {
        // for PSR-7 compatible server requests
        if (is_object($request) && method_exists($request, 'getParsedBody')) {
            return $request->getParsedBody();
        }
        // for everyone else
        return $_POST;
    }

    /**
     * Summary of getJsonBody
     * @param mixed $request
     * @return mixed
     */
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
