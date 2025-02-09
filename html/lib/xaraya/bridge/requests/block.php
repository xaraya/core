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
 */
class BlockRequest extends BasicBridge implements BlockBridgeInterface
{
    use BlockBridgeTrait;

    public function setContext($context)
    {
        $this->block()->setContext($context);
        parent::setContext($context);
    }
}

class BlockGuiRequest extends BlockRequest
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

class BlockApiRequest extends BlockRequest
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
