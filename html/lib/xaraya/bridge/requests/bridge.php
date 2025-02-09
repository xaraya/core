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

use Xaraya\Routing\RouterInterface;

/**
 * Bridge for generic requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */
class BasicBridge extends BasicRequest implements BasicBridgeInterface
{
    use BasicBridgeTrait;

    /** @var RouterInterface|null */
    public $router = null;

    public function __construct(?RouterInterface $router = null)
    {
        $this->router = $router;
    }

    /**
     * Summary of getRouter
     * @return RouterInterface|null
     */
    public function getRouter()
    {
        return $this->router;
    }
}
