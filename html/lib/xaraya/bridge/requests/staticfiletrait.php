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

/**
 * Handle static file requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */
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
            // $type = module: {prefix}/{source}/{folder}/{file} = file /code/modules/{dynamicdata}/{xartemplates}/{style/dd.css}
            // $type = theme: {prefix}/{source}/{folder}/{file} = file /themes/{default}/...
            // $type = var: {prefix}/{source}/{folder}/{file} = file /var/{cache}/{api}/...
            $params['static'] = $type;
            $params['source'] = $pieces[0];
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
     * Summary of parseVarFilePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @param string $type
     * @return array<string, mixed>
     */
    public static function parseVarFilePath(string $path = '/', array $query = [], string $prefix = '/var', string $type = 'var'): array
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
        $uri = $prefix;
        // {prefix}/{source}/{folder}/{file} = file /code/modules/{dynamicdata}/{xartemplates}/{style/dd.css}
        // {prefix}/{source}/{folder}/{file} = file /themes/{default}/...
        // {prefix}/{source}/{folder}/{file} = file /var/{cache}/{api}/...
        $uri .= '/' . $source . '/' . $folder . '/' . $file;
        //if (!empty($extra)) {
        //    $uri .= '?' . http_build_query($extra);
        //}
        return $uri;
    }

    /**
     * Summary of buildModuleFilePath
     * @param string $source
     * @param string $folder
     * @param string $file
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildModuleFilePath(string $source = 'base', string $folder = null, string $file = null, array $extra = [], string $prefix = '/code/modules'): string
    {
        return static::buildStaticFilePath($source, $folder, $file, $extra, $prefix);
    }

    /**
     * Summary of buildThemeFilePath
     * @param string $source
     * @param string $folder
     * @param string $file
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildThemeFilePath(string $source = 'default', string $folder = null, string $file = null, array $extra = [], string $prefix = '/themes'): string
    {
        return static::buildStaticFilePath($source, $folder, $file, $extra, $prefix);
    }

    /**
     * Summary of buildVarFilePath
     * @param string $source
     * @param string $folder
     * @param string $file
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildVarFilePath(string $source = 'cache', string $folder = null, string $file = null, array $extra = [], string $prefix = '/var'): string
    {
        return static::buildStaticFilePath($source, $folder, $file, $extra, $prefix);
    }

    /**
     * Summary of getStaticFileRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string
     */
    public static function getStaticFileRequest($params): string
    {
        if (empty($params['static'])) {
            throw new Exception("Missing static parameter");
        }
        if (empty($params['source'])) {
            throw new Exception("Missing source parameter");
        }
        if (empty($params['folder'])) {
            throw new Exception("Missing folder parameter");
        }
        if (empty($params['file'])) {
            throw new Exception("Missing file parameter");
        }
        // return filepath, stream, ... ?
        switch ($params['static']) {
            case 'module':
                return static::getModuleFileRequest($params);
            case 'theme':
                return static::getThemeFileRequest($params);
            case 'var':
                return static::getVarFileRequest($params);
            case 'other':
                return static::getOtherFileRequest($params);
            default:
                throw new Exception("Invalid static parameter");
        }
    }

    /**
     * Summary of getModuleFileRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string
     */
    public static function getModuleFileRequest($params): string
    {
        $path = sys::code() . 'modules/' . $params['source'] . '/' . $params['folder'] . '/' . $params['file'];
        $real = realpath($path);
        if (empty($real)) {
            throw new Exception("Invalid file");
        }
        $pieces = explode('.', $real);
        $ext = array_pop($pieces);
        if (!in_array($ext, static::$extensions)) {
            throw new Exception("Invalid file extension");
        }
        $module = realpath(sys::code() . 'modules/' . $params['source'] . '/');
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
        $path = sys::web() . 'themes/' . $params['source'] . '/' . $params['folder'] . '/' . $params['file'];
        $real = realpath($path);
        if (empty($real)) {
            throw new Exception("Invalid file");
        }
        $pieces = explode('.', $real);
        $ext = array_pop($pieces);
        if (!in_array($ext, static::$extensions)) {
            throw new Exception("Invalid file extension");
        }
        $theme = realpath(sys::web() . 'themes/' . $params['source'] . '/');
        if (empty($theme) || strpos($real, $theme) !== 0) {
            throw new Exception("Invalid file path");
        }
        return $real;
    }

    /**
     * Summary of getVarFileRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string
     */
    public static function getVarFileRequest($params): string
    {
        $path = sys::varpath() . '/' . $params['source'] . '/' . $params['folder'] . '/' . $params['file'];
        $real = realpath($path);
        if (empty($real)) {
            throw new Exception("Invalid file");
        }
        $pieces = explode('.', $real);
        $ext = array_pop($pieces);
        if (!in_array($ext, static::$extensions)) {
            throw new Exception("Invalid file extension");
        }
        $theme = realpath(sys::varpath() . '/' . $params['source'] . '/');
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
