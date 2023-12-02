<?php
/**
 * Handle static file requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Exception;
use sys;

/**
 * For documentation purposes only - available via StaticFileBridgeTrait
 */
interface StaticFileBridgeInterface extends CommonRequestInterface
{
    /**
     * Summary of parseStaticFilePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @param string $type
     * @return array<string, mixed>
     */
    public static function parseStaticFilePath(string $path = '/', array $query = [], string $prefix = '', string $type = 'theme'): array;

    /**
     * Summary of buildStaticFilePath
     * @param string $source
     * @param string $folder
     * @param string $file
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildStaticFilePath(string $source = 'default', string $folder = null, string $file = null, array $extra = [], string $prefix = ''): string;

    /**
     * Summary of getStaticFileRequest
     * @param array<string, mixed> $params
     * @return string
     */
    public static function getStaticFileRequest($params): string;
}

trait StaticFileBridgeTrait
{
    // @todo check extensions + use mime_content_type() or equivalent
    /** @var array<string> */
    protected static array $extensions = ['png', 'jpg', 'gif', 'css', 'js', 'htm', 'html', 'txt', 'xml', 'json', 'ico'];

    /**
     * Summary of parseStaticFilePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @param string $type
     * @return array<string, mixed>
     */
    public static function parseStaticFilePath(string $path = '/', array $query = [], string $prefix = '', string $type = 'theme'): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && strpos($path, $prefix . '/') === 0) {
            // max. 3 pieces here - file will contain remaining / if any
            $pieces = explode('/', substr($path, strlen($prefix) + 1), 3);
            if (count($pieces) < 3) {
                return [];
            }
            // {prefix}/{module}/{folder}/{file} = file /code/modules/{dynamicdata}/{xartemplates}/{style/dd.css}
            // {prefix}/{theme}/{folder}/{file} = file /themes/{default}/...
            $params[$type] = $pieces[0];
            $params['folder'] = $pieces[1];
            $params['file'] = $pieces[2];
        }
        // add remaining query params to path params
        //$params = array_merge($params, $query);
        return $params;
    }

    /**
     * Summary of parseModuleFilePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @param string $type
     * @return array<string, mixed>
     */
    public static function parseModuleFilePath(string $path = '/', array $query = [], string $prefix = '/code/modules', string $type = 'module'): array
    {
        return static::parseStaticFilePath($path, $query, $prefix, $type);
    }

    /**
     * Summary of parseThemeFilePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @param string $type
     * @return array<string, mixed>
     */
    public static function parseThemeFilePath(string $path = '/', array $query = [], string $prefix = '/themes', string $type = 'theme'): array
    {
        return static::parseStaticFilePath($path, $query, $prefix, $type);
    }

    /**
     * Summary of buildStaticFilePath
     * @param string $source
     * @param string $folder
     * @param string $file
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildStaticFilePath(string $source = 'default', string $folder = null, string $file = null, array $extra = [], string $prefix = ''): string
    {
        // see xarTheme::image()
        $uri = static::$baseUri;
        if (!empty($prefix) && strstr($uri, $prefix) !== $prefix) {
            $uri .= $prefix;
        }
        // {prefix}/{source}/{folder}/{file} = file /code/modules/{dynamicdata}/{xartemplates}/{style/dd.css} or /themes/{default}/...
        $uri .= '/' . $source . '/' . $folder . '/' . $file;
        //if (!empty($extra)) {
        //    $uri .= '?' . http_build_query($extra);
        //}
        return $uri;
    }

    /**
     * Summary of buildModuleFilePath
     * @param string $module
     * @param string $folder
     * @param string $file
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildModuleFilePath(string $module = 'base', string $folder = null, string $file = null, array $extra = [], string $prefix = '/code/modules'): string
    {
        return static::buildStaticFilePath($module, $folder, $file, $extra, $prefix);
    }

    /**
     * Summary of buildThemeFilePath
     * @param string $theme
     * @param string $folder
     * @param string $file
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildThemeFilePath(string $theme = 'default', string $folder = null, string $file = null, array $extra = [], string $prefix = '/themes'): string
    {
        return static::buildStaticFilePath($theme, $folder, $file, $extra, $prefix);
    }

    /**
     * Summary of getStaticFileRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string
     */
    public static function getStaticFileRequest($params): string
    {
        if (empty($params['folder'])) {
            throw new Exception("Missing folder parameter");
        }
        if (empty($params['file'])) {
            throw new Exception("Missing file parameter");
        }
        // return filepath, stream, ... ?
        if (!empty($params['module'])) {
            return static::getModuleFileRequest($params);
        } elseif (!empty($params['theme'])) {
            if ($params['theme'] === 'none') {
                return static::getOtherFileRequest($params);
            }
            return static::getThemeFileRequest($params);
        }
        throw new Exception("Missing module or theme parameter");
    }

    /**
     * Summary of getModuleFileRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string
     */
    public static function getModuleFileRequest($params): string
    {
        $path = sys::code() . 'modules/' . $params['module'] . '/' . $params['folder'] . '/' . $params['file'];
        $real = realpath($path);
        if (empty($real)) {
            throw new Exception("Invalid file");
        }
        $pieces = explode('.', $real);
        $ext = array_pop($pieces);
        if (!in_array($ext, static::$extensions)) {
            throw new Exception("Invalid file extension");
        }
        $module = realpath(sys::code() . 'modules/' . $params['module'] . '/');
        if (empty($module) || strpos($real, $module) !== 0) {
            throw new Exception("Invalid file path");
        }
        return $real;
    }

    /**
     * Summary of getThemeFileRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string
     */
    public static function getThemeFileRequest($params): string
    {
        $path = sys::web() . 'themes/' . $params['theme'] . '/' . $params['folder'] . '/' . $params['file'];
        $real = realpath($path);
        if (empty($real)) {
            throw new Exception("Invalid file");
        }
        $pieces = explode('.', $real);
        $ext = array_pop($pieces);
        if (!in_array($ext, static::$extensions)) {
            throw new Exception("Invalid file extension");
        }
        $theme = realpath(sys::web() . 'themes/' . $params['theme'] . '/');
        if (empty($theme) || strpos($real, $theme) !== 0) {
            throw new Exception("Invalid file path");
        }
        return $real;
    }

    /**
     * Summary of getOtherFileRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string
     */
    public static function getOtherFileRequest($params): string
    {
        if ($params['folder'] !== 'web') {
            throw new Exception("Invalid file path");
        }
        $path = sys::web() . $params['file'];
        $real = realpath($path);
        if (empty($real)) {
            throw new Exception("Invalid file");
        }
        return $real;
    }
}
