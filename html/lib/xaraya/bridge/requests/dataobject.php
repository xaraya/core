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
 * Handle DataObject requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */
class DataObjectRequestHandler extends BasicBridge implements DataObjectBridgeInterface
{
    use DataObjectBridgeTrait;

    /**
     * Get DataObject handler routes (in generic format)
     * @param string $pathPrefix
     * @param string $namePrefix
     * @param mixed $handler
     * @param array<mixed> $extra
     * @return array<mixed> array of name => [method(s), path, handler, options = []]
     */
    public static function getRoutes(string $pathPrefix = '', string $namePrefix = '', mixed $handler = null, array $extra = []): array
    {
        return static::getDataObjectRoutes($pathPrefix, $namePrefix, $handler, $extra);
    }

    public function setContext($context)
    {
        $this->data()->setContext($context);
        parent::setContext($context);
    }
}

class DataObjectGuiHandler extends DataObjectRequestHandler
{
    /**
     * Summary of runDataObjectRequest
     * @param array<string, mixed> $params
     * @return string|null
     */
    public function runDataObjectRequest($params): ?string
    {
        return $this->runDataObjectGuiRequest($params);
    }
}

class DataObjectApiHandler extends DataObjectRequestHandler
{
    /**
     * Summary of runDataObjectRequest
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function runDataObjectRequest($params): mixed
    {
        return $this->runDataObjectApiRequest($params);
    }
}
