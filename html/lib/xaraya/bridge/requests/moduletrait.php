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
use Xaraya\Services\ModulesInterface;
use Xaraya\Services\ServiceFactory;
use Xaraya\Context\ContextFactory;

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
    public function parseModulePath(string $path = '/', array $query = [], string $prefix = ''): array;

    /**
     * Summary of buildModulePath
     * @param string $module
     * @param ?string $type
     * @param string|int|null $func
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildModulePath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string;

    /**
     * Summary of runModuleGuiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return string|null
     */
    public function runModuleGuiRequest($vars, $query): ?string;

    /**
     * Summary of runModuleApiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return mixed
     */
    public function runModuleApiRequest($vars, $query): mixed;
}

/**
 * Handle Module requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */
trait ModuleBridgeTrait
{
    public static string $baseUri = '';
    public static string $prefix = '';
    protected ?ModulesInterface $xarMod = null;

    public function mod(): ModulesInterface
    {
        $this->xarMod ??= ServiceFactory::getModulesService($this);
        return $this->xarMod;
    }

    /**
     * Get Module handler routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $handler
     * @param array<mixed> $extra
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getModuleRoutes(string $pathPrefix = '', string $namePrefix = '', mixed $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        // without trailing /
        $path = $pathPrefix . '/{module}';
        $name = $namePrefix . 'module';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleModuleRequest'], $extra];

        $path = $pathPrefix . '/{module}/{func}';
        $name = $namePrefix . 'module-func';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleModuleRequest'], $extra];

        $path = $pathPrefix . '/{module}/{type}/{func}';
        $name = $namePrefix . 'module-type-func';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleModuleRequest'], $extra];

        $path = $pathPrefix . '/';
        $name = $namePrefix . 'root';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleModuleRequest'], $extra];

        return $routes;
    }

    /**
     * Summary of parseModulePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseModulePath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && str_starts_with($path, $prefix . '/')) {
            $pieces = explode('/', substr($path, strlen($prefix) + 1));
            // {prefix}/{module} = user main
            $params['module'] = $pieces[0];
            if ($params['module'] == 'object') {
                // see DataObjectBridgeTrait with prefix /object
                $handler = new DataObjectRequestHandler();
                return $handler->parseDataObjectPath($path, $query, $prefix . '/object');
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
     * @todo do we want to keep this static?
     */
    public function buildModulePath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string
    {
        if ($module == 'object') {
            $itemid = $extra['itemid'] ?? null;
            unset($extra['itemid']);
            // see DataObjectBridgeTrait with prefix /object
            $prefix .= '/object';
            $handler = new DataObjectRequestHandler();
            return $handler->buildDataObjectPath($type, $func, $itemid, $extra, $prefix);
        }
        // see xarServer::getModuleURL()
        $uri = $prefix;
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
     * Summary of handleModuleRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleModuleRequest($vars, &$request = null)
    {
        // path = /
        $vars['module'] ??= 'base';
        // path = /object[/...]
        if ($vars['module'] == 'object') {
            // @todo figure out if we need GUI or API DataObject request handler here
            if ($this instanceof ModuleApiHandler) {
                $handler = new DataObjectApiHandler($this->getRouter());
            } else {
                $handler = new DataObjectGuiHandler($this->getRouter());
            }
            return $handler->handleObjectRequest($vars, $request);
        }
        // path = /{module}/{func}
        if (empty($vars['type']) && !empty($vars['func'])) {
            $vars['type'] = 'user';
        } elseif (!empty($vars['type']) && empty($vars['func'])) {
            $vars['func'] = $vars['type'];
            $vars['type'] = 'user';
        }
        // path = /{module}/{type}/{func}
        // dispatcher doesn't provide query params by default
        $query = $this->getQueryParams($request);
        // filter out path vars from remaining query params here
        $params = array_diff_key($query, $vars);
        // add body params to query params (if any)
        $input = $this->getParsedBody($request);
        if (!empty($input) && is_array($input)) {
            $params = array_merge($params, $input);
        }

        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        static::$baseUri = $this->getBaseUri($request) . static::$prefix;
        $context['baseuri'] = static::$baseUri;
        // set current module to 'module' for Xaraya controller - used e.g. in xarMod::getName()
        $this->prepareController($vars['module'], static::$baseUri);
        $context['module'] = $vars['module'];
        // @todo check if we already have a context? (via request or from elsewhere)
        $this->setContext($context);

        // @todo allow overriding this for RoutingApiBridge vs. RoutingBridge
        $result = $this->runModuleRequest($vars, $params);
        return [$result, $context];
    }

    /**
     * Summary of runModuleGuiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return string|null
     */
    public function runModuleGuiRequest($vars, $query): ?string
    {
        return $this->mod()->guiFunc($vars['module'], $vars['type'] ?? 'user', $vars['func'] ?? 'main', $query);
    }

    /**
     * Summary of runModuleApiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return mixed
     */
    public function runModuleApiRequest($vars, $query): mixed
    {
        return $this->mod()->apiFunc($vars['module'], $vars['type'] ?? 'user', $vars['func'] ?? 'getitemtypes', $query);
    }
}
