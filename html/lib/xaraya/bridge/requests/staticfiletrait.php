<?php

/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Xaraya\Context\ContextFactory;
use Xaraya\Context\Context;
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
    public function parseStaticFilePath(string $path = '/', array $query = [], string $prefix = '', string $type = 'theme'): array;

    /**
     * Summary of buildStaticFilePath
     * @param string $source
     * @param string $folder
     * @param string $file
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildStaticFilePath(string $source = 'default', ?string $folder = null, ?string $file = null, array $extra = [], string $prefix = ''): string;

    /**
     * Summary of getStaticFileRequest
     * @param array<string, mixed> $params
     * @return string
     */
    public function getStaticFileRequest($params): string;
}

/**
 * Handle static file requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * @phpstan-import-type RouteDef from BridgeRequest
 */
trait StaticFileBridgeTrait
{
    // @todo check extensions + use mime_content_type() or equivalent
    /** @var array<string> */
    protected static array $extensions = ['png', 'jpg', 'gif', 'css', 'js', 'htm', 'html', 'txt', 'xml', 'json', 'ico'];

    /**
     * Summary of getRoutes
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param ?string $handler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getStaticFileRoutes(string $pathPrefix = '', string $namePrefix = 'static-', ?string $handler = null, ?array $extra = null): array
    {
        $handler ??= static::class;
        $routes = [];

        $path = $pathPrefix . '/code/modules/{source}/{folder}/{file:.+}';
        $name = $namePrefix . 'module-file';
        $routes[$name] = ['GET', $path, [$handler, 'handleModuleFileRequest'], $extra];

        $path = $pathPrefix . '/themes/{source}/{folder}/{file:.+}';
        $name = $namePrefix . 'theme-file';
        $routes[$name] = ['GET', $path, [$handler, 'handleThemeFileRequest'], $extra];

        $path = $pathPrefix . '/var/{source}/{folder}/{file:.+}';
        $name = $namePrefix . 'var-file';
        $routes[$name] = ['GET', $path, [$handler, 'handleVarFileRequest'], $extra];

        return $routes;
    }

    /**
     * Summary of parseStaticFilePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @param string $type
     * @return array<string, mixed>
     */
    public function parseStaticFilePath(string $path = '/', array $query = [], string $prefix = '', string $type = 'theme'): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && str_starts_with($path, $prefix . '/')) {
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
    public function parseModuleFilePath(string $path = '/', array $query = [], string $prefix = '/code/modules', string $type = 'module'): array
    {
        return $this->parseStaticFilePath($path, $query, $prefix, $type);
    }

    /**
     * Summary of parseThemeFilePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @param string $type
     * @return array<string, mixed>
     */
    public function parseThemeFilePath(string $path = '/', array $query = [], string $prefix = '/themes', string $type = 'theme'): array
    {
        return $this->parseStaticFilePath($path, $query, $prefix, $type);
    }

    /**
     * Summary of parseVarFilePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @param string $type
     * @return array<string, mixed>
     */
    public function parseVarFilePath(string $path = '/', array $query = [], string $prefix = '/var', string $type = 'var'): array
    {
        return $this->parseStaticFilePath($path, $query, $prefix, $type);
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
    public function buildStaticFilePath(string $source = 'default', ?string $folder = null, ?string $file = null, array $extra = [], string $prefix = ''): string
    {
        // see xar::tpl()->image()
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
    public function buildModuleFilePath(string $source = 'base', ?string $folder = null, ?string $file = null, array $extra = [], string $prefix = '/code/modules'): string
    {
        return $this->buildStaticFilePath($source, $folder, $file, $extra, $prefix);
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
    public function buildThemeFilePath(string $source = 'default', ?string $folder = null, ?string $file = null, array $extra = [], string $prefix = '/themes'): string
    {
        return $this->buildStaticFilePath($source, $folder, $file, $extra, $prefix);
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
    public function buildVarFilePath(string $source = 'cache', ?string $folder = null, ?string $file = null, array $extra = [], string $prefix = '/var'): string
    {
        return $this->buildStaticFilePath($source, $folder, $file, $extra, $prefix);
    }

    /**
     * Basic route builder for static file requests e.g. in response output or templates - assuming short url format here
     * @param array<string, mixed> $extra
     */
    public function buildUri(?string $source = null, ?string $folder = null, string|int|null $file = null, array $extra = [], string $prefix = ''): string
    {
        $uri = static::$baseUri;
        if (!empty($prefix) && strstr($uri, $prefix) !== $prefix) {
            $uri .= $prefix;
        }
        return $this->buildStaticFilePath($source, $folder, $file, $extra, $uri);
    }

    /**
     * Summary of setRequestContext
     * @param mixed $request
     * @return Context<string, mixed>
     */
    public function setRequestContext(&$request = null)
    {
        $context = ContextFactory::fromRequest($request, __METHOD__);
        // Set context for core services here first!?
        // xar::setServicesContext($context);
        //$context['mediatype'] = '';
        //static::$baseUri = $this->getBaseUri($request) . static::$prefix;
        //$context['baseuri'] = static::$baseUri;
        // set current module to 'module' for Xaraya controller - used e.g. in xar::mod()->getName()
        //$this->prepareController($vars['module'] ?? 'base', static::$baseUri);
        //$context['module'] = $vars['module'] ?? 'base';
        return $context;
    }

    /**
     * Summary of handleThemeFileRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleThemeFileRequest($vars, &$request = null)
    {
        // path = /themes/{source}/{folder}/{file:.+}
        $path = $this->getThemeFileRequest($vars);
        $vars['path'] = $path;
        $vars['static'] = 'theme';
        if (file_exists($path)) {
            $vars['size'] = filesize($path);
            $vars['mtime'] = filemtime($path);
        }
        //if (!empty($request)) {
        //    $request = $request->withAttribute('mediaType', '...');
        //}
        //$context = $this->setRequestContext($request);
        // @todo check if we already have a context? (via request or from elsewhere)
        //$this->setContext($context);

        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return [var_export($vars, true), null];
    }

    /**
     * Summary of handleModuleFileRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleModuleFileRequest($vars, &$request = null)
    {
        // path = /code/modules/{source}/{folder}/{file:.+}
        $path = $this->getModuleFileRequest($vars);
        $vars['path'] = $path;
        $vars['static'] = 'module';
        if (file_exists($path)) {
            $vars['size'] = filesize($path);
            $vars['mtime'] = filemtime($path);
        }
        //if (!empty($request)) {
        //    $request = $request->withAttribute('mediaType', '...');
        //}
        //$context = $this->setRequestContext($request);
        // @todo check if we already have a context? (via request or from elsewhere)
        //$this->setContext($context);

        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return [var_export($vars, true), null];
    }

    /**
     * Summary of handleVarFileRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleVarFileRequest($vars, &$request = null)
    {
        // path = /var/{source}/{folder}/{file:.+}
        $path = $this->getVarFileRequest($vars);
        $vars['path'] = $path;
        $vars['static'] = 'var';
        if (file_exists($path)) {
            $vars['size'] = filesize($path);
            $vars['mtime'] = filemtime($path);
        }
        //if (!empty($request)) {
        //    $request = $request->withAttribute('mediaType', '...');
        //}
        //$context = $this->setRequestContext($request);
        // @todo check if we already have a context? (via request or from elsewhere)
        //$this->setContext($context);

        // @todo where do we handle NotModified response based on request header If-None-Match etc.?
        return [var_export($vars, true), null];
    }

    /**
     * Summary of getStaticFileRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string
     */
    public function getStaticFileRequest($params): string
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
        return match ($params['static']) {
            'module' => $this->getModuleFileRequest($params),
            'theme' => $this->getThemeFileRequest($params),
            'var' => $this->getVarFileRequest($params),
            'other' => $this->getOtherFileRequest($params),
            default => throw new Exception("Invalid static parameter"),
        };
    }

    /**
     * Summary of getModuleFileRequest
     * @param array<string, mixed> $params
     * @throws \Exception
     * @return string
     */
    public function getModuleFileRequest($params): string
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
        if (empty($module) || !str_starts_with($real, $module)) {
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
    public function getThemeFileRequest($params): string
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
        if (empty($theme) || !str_starts_with($real, $theme)) {
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
    public function getVarFileRequest($params): string
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
        $var = realpath(sys::varpath() . '/' . $params['source'] . '/');
        if (empty($var) || !str_starts_with($real, $var)) {
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
    public function getOtherFileRequest($params): string
    {
        if ($params['folder'] !== 'web') {
            throw new Exception("Invalid file path");
        }
        $path = sys::web() . $params['file'];
        $real = realpath($path);
        if (empty($real)) {
            throw new Exception("Invalid file");
        }
        $web = realpath(sys::web() . '/');
        if (empty($web) || !str_starts_with($real, $web)) {
            throw new Exception("Invalid file path");
        }
        return $real;
    }
}
