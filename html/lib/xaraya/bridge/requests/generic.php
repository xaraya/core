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

/**
 * Handle Generic requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * @phpstan-import-type RouteDef from BridgeRequest
 */
class GenericRequestHandler extends BridgeRequest implements GenericBridgeInterface
{
    use GenericBridgeTrait;

    /**
     * Get Generic handler routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param ?string $handler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = '', ?string $handler = null, array $extra = []): array
    {
        return static::getGenericRoutes($pathPrefix, $namePrefix, $handler, $extra);
    }

    public function setContext($context)
    {
        if (isset($context)) {
            $context->handler = $this;
        }
        parent::setContext($context);
        //$this->mod()->setContext($context);
    }
}

class GenericGuiHandler extends GenericRequestHandler
{
    /**
     * Summary of runGenericRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return string|null
     */
    public function runGenericRequest($vars, $query): ?string
    {
        return $this->runGenericGuiRequest($vars, $query);
    }

    /**
     * Summary of runRoutesRequest
     * @param array<string, mixed> $vars
     * @return string
     */
    public function runRoutesRequest($vars)
    {
        return $this->runRoutesGuiRequest($vars);
    }
}

class GenericApiHandler extends GenericRequestHandler
{
    /**
     * Summary of runGenericRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return mixed
     */
    public function runGenericRequest($vars, $query): mixed
    {
        return $this->runGenericApiRequest($vars, $query);
    }

    /**
     * Summary of runRoutesRequest
     * @param array<string, mixed> $vars
     * @return mixed
     */
    public function runRoutesRequest($vars)
    {
        return $this->runRoutesApiRequest($vars);
    }
}
