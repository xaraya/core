<?php
/**
 * @package core\bridge
 * @subpackage middleware
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use sys;

sys::import('xaraya.bridge.requests.bridge');
use Xaraya\Bridge\Requests\BasicBridge;

/**
 * Default router interface for PSR-7 and PSR-15 compatible middleware controllers
 */
interface DefaultRouterInterface
{
    public function matchRequest(ServerRequestInterface $request): ServerRequestInterface;

    /**
     * Summary of parseUri
     * @param ServerRequestInterface $request
     * @return array<string, mixed>
     */
    public function parseUri(ServerRequestInterface $request): array;

    /**
     * Summary of buildUri - @checkme signature might be different for other routers - keep it generic here
     * @param ?string $arg1
     * @param ?string $arg2
     * @param string|int|null $arg3
     * @param array<string, mixed> $extra
     * @return string
     */
    public function buildUri(?string $arg1 = null, ?string $arg2 = null, string|int|null $arg3 = null, array $extra = []): string;
}

/**
 * Middleware should be built by creating a customized router and then adding the processsing - extend this to create your router
 */
abstract class DefaultRouter extends BasicBridge implements DefaultRouterInterface
{
    public static string $baseUri = '';
    public static string $prefix = '';

    /**
     * Basic route matcher to identify object/module requests and set request attributes e.g. in router middleware
     */
    abstract public function matchRequest(ServerRequestInterface $request): ServerRequestInterface;

    /**
     * Basic route parser for object/module requests e.g. in route matcher for router middleware
     */
    abstract public function parseUri(ServerRequestInterface $request): array;

    /**
     * Basic route builder for object/module requests e.g. in response output or templates - assuming short url format here
     *
     * @checkme signature might be different for other routers - keep it generic here
     * public function buildUri(string $object, string $method = '', string|int|null $itemid = null, array $extra = []): string;
     * public function buildUri($modName = null, $modType = 'user', $funcName = 'main', $args = []): string;
     */
    abstract public function buildUri(?string $arg1 = null, ?string $arg2 = null, string|int|null $arg3 = null, array $extra = []): string;
}
