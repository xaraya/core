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
 * Handle Block requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * @phpstan-import-type RouteDef from BridgeRequest
 */
class BlockRequestHandler extends BridgeRequest implements BlockBridgeInterface
{
    use BlockBridgeTrait;

    /**
     * Get Block handler routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param ?string $handler
     * @param array<mixed> $extra
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = '', ?string $handler = null, array $extra = []): array
    {
        return static::getBlockRoutes($pathPrefix, $namePrefix, $handler, $extra);
    }

    public function setContext($context)
    {
        if (isset($context)) {
            $context->handler = $this;
        }
        parent::setContext($context);
        $this->block()->setContext($context);
    }
}

class BlockGuiHandler extends BlockRequestHandler
{
    /**
     * Summary of runBlockRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return string
     */
    public function runBlockRequest($vars, $query = null): string
    {
        return $this->runBlockGuiRequest($vars, $query);
    }
}

class BlockApiHandler extends BlockRequestHandler
{
    /**
     * Summary of runBlockRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return array<mixed>
     */
    public function runBlockRequest($vars, $query = null): array
    {
        return $this->runBlockApiRequest($vars, $query);
    }
}
