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

/**
 * For documentation purposes only - available via GenericBridgeTrait
 */
interface GenericBridgeInterface extends CommonRequestInterface
{
    /**
     * Summary of parseGenericPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseGenericPath(string $path = '/', array $query = [], string $prefix = ''): array;

    /**
     * Summary of buildGenericPath
     * @param string $module
     * @param ?string $type
     * @param string|int|null $func
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildGenericPath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string;

    /**
     * Summary of runGenericGuiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return string|null
     */
    public function runGenericGuiRequest($vars, $query): ?string;

    /**
     * Summary of runGenericApiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return mixed
     */
    public function runGenericApiRequest($vars, $query): mixed;
}

/**
 * Handle Generic requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * @phpstan-import-type RouteDef from BasicBridge
 */
trait GenericBridgeTrait
{
    public static string $baseUri = '';
    public static string $prefix = '';

    /**
     * Get Generic handler routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param ?string $handler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getGenericRoutes(string $pathPrefix = '', string $namePrefix = '', ?string $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        $path = $pathPrefix . '/routes';
        $name = $namePrefix . 'routes';
        $routes[$name] = ['GET', $path, [$handler, 'handleRoutesRequest'], $extra];

        $path = $pathPrefix . '/';
        $name = $namePrefix . 'root';
        $routes[$name] = [['GET', 'POST'], $path, [$handler, 'handleGenericRequest'], $extra];

        return $routes;
    }

    /**
     * Summary of parseGenericPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseGenericPath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && str_starts_with($path, $prefix . '/')) {
            $pieces = explode('/', substr($path, strlen($prefix) + 1));
            // {prefix}/{route} = route
            $params['route'] = $pieces[0];
            // @todo handle type and path
        }
        // add remaining query params to path params
        $params = array_merge($params, $query);
        return $params;
    }

    /**
     * Summary of buildGenericPath
     * @param string $route
     * @param ?string $type
     * @param string|int|null $path
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildGenericPath(string $route = '', ?string $type = null, string|int|null $path = null, array $extra = [], string $prefix = ''): string
    {
        // @todo see xarServer::getCurrentURL()
        $uri = $prefix;
        // {prefix}/{route} = route
        $uri .= '/' . $route;
        return $uri;
    }

    /**
     * Summary of runGenericGuiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return string|null
     */
    public function runGenericGuiRequest($vars, $query): ?string
    {
        return 'Generic GUI Request';
    }

    /**
     * Summary of runGenericApiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return mixed
     */
    public function runGenericApiRequest($vars, $query): mixed
    {
        return ['result' => 'Generic API Request'];
    }

    /**
     * Show available routes
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleRoutesRequest($vars, &$request = null)
    {
        // @todo allow overriding this for RoutingApiBridge vs. RoutingBridge
        $result = $this->runRoutesRequest($vars);
        return [$result, null];
    }

    /**
     * Summary of runRoutesGuiRequest
     * @param array<string, mixed> $vars
     * @return string
     */
    public function runRoutesGuiRequest($vars)
    {
        $result = "<ul>";
        foreach ($this->getRouter()->getRoutes() as $name => $route) {
            $result .= "<li>" . $name . " [" . json_encode($route, JSON_UNESCAPED_SLASHES) . "]</li>";
        }
        $result .= "</ul>";
        return $result;
    }

    /**
     * Summary of runRoutesApiRequest
     * @param array<string, mixed> $vars
     * @return mixed
     */
    public function runRoutesApiRequest($vars)
    {
        $result = [];
        foreach ($this->getRouter()->getRoutes() as $name => $route) {
            $result[$name] = $route;
        }
        return $result;
    }
}
