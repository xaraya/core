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
use Xaraya\Services\BlocksInterface;
use Xaraya\Services\ServiceFactory;
use Xaraya\Context\ContextFactory;

/**
 * For documentation purposes only - available via BlockBridgeTrait
 */
interface BlockBridgeInterface extends CommonRequestInterface
{
    /**
     * Summary of parseBlockPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseBlockPath(string $path = '/', array $query = [], string $prefix = ''): array;

    /**
     * Summary of buildBlockPath
     * @param string|int $type
     * @param ?string $method
     * @param string|int|null $instance
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildBlockPath(string|int $type = 'menu', ?string $method = null, string|int|null $instance = null, array $extra = [], string $prefix = '/block'): string;

    /**
     * Summary of runBlockGuiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return string
     */
    public function runBlockGuiRequest($vars, $query = null): string;

    /**
     * Summary of runBlockApiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return array<mixed>
     */
    public function runBlockApiRequest($vars, $query = null): array;
}

/**
 * Handle Block requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * @phpstan-import-type RouteDef from BridgeRequest
 */
trait BlockBridgeTrait
{
    public static string $baseUri = '';
    public static string $prefix = '';
    protected ?BlocksInterface $xarBlock = null;

    public function block(): BlocksInterface
    {
        if (!isset($this->xarBlock)) {
            $xar = $this->getServicesClass();
            $this->xarBlock = $xar->block();
        }
        return $this->xarBlock;
    }

    /**
     * Get Block handler routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param ?string $handler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getBlockRoutes(string $pathPrefix = '', string $namePrefix = '', ?string $handler = null, array $extra = []): array
    {
        $handler ??= static::class;
        $routes = [];

        $path = $pathPrefix . '/block/{instance}';
        $name = $namePrefix . 'block-instance';
        $routes[$name] = ['GET', $path, [$handler, 'handleBlockRequest'], $extra];

        return $routes;
    }

    /**
     * Summary of parseBlockPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseBlockPath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        // @todo
        return [];
    }

    /**
     * Summary of buildBlockPath
     * @param string|int $type
     * @param ?string $method
     * @param string|int|null $instance
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildBlockPath(string|int $type = 'menu', ?string $method = null, string|int|null $instance = null, array $extra = [], string $prefix = '/block'): string
    {
        // @todo
        return '/';
    }

    /**
     * Summary of handleBlockRequest
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return array<mixed>
     */
    public function handleBlockRequest($vars, &$request = null)
    {
        // @checkme limited to renderBlock() or getinfo() for now, so no query params or body params taken into account yet
        // dispatcher doesn't provide query params by default
        $query = $this->getQueryParams($request);

        $context = ContextFactory::fromRequest($request, __METHOD__);
        // Set context for core services here first!?
        // xar::setServicesContext($context);
        $context['mediatype'] = '';
        static::$baseUri = $this->getBaseUri($request) . static::$prefix;
        $context['baseuri'] = static::$baseUri;
        // set current module to 'module' for Xaraya controller - used e.g. in xar::req()->getModule()
        $this->prepareController($vars['module'] ?? 'base', static::$baseUri);
        $context['module'] = $vars['module'] ?? 'base';
        // @todo check if we already have a context? (via request or from elsewhere)
        $this->setContext($context);

        // @todo allow overriding this for RoutingApiBridge vs. RoutingBridge
        $result = $this->runBlockRequest($vars, $query);
        return [$result, $context];
    }

    // @checkme limited to renderBlock() for now
    /**
     * Summary of runBlockGuiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return string
     */
    public function runBlockGuiRequest($vars, $query = null): string
    {
        if (!empty($query)) {
            $vars = array_merge($vars, $query);
        }
        return $this->block()->guiRequest($vars);
    }

    /**
     * Summary of runBlockApiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return array<mixed>
     */
    public function runBlockApiRequest($vars, $query = null): array
    {
        if (!empty($query)) {
            $vars = array_merge($vars, $query);
        }
        return $this->block()->apiRequest($vars);
    }
}
