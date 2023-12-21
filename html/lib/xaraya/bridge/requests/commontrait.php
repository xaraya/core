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

/**
 * For documentation purposes only - available via CommonBridgeTrait
 */
interface CommonBridgeInterface extends BasicBridgeInterface, CommonRequestInterface, DataObjectBridgeInterface, ModuleBridgeInterface, BlockBridgeInterface
{
}

/**
 * Handle common requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * Accepts PSR-7 compatible server requests, xarRequest (partial use) or nothing (using $_SERVER)
 */
trait CommonBridgeTrait
{
    //use BasicBridgeTrait;
    //use CommonRequestTrait;
    use DataObjectBridgeTrait;
    use ModuleBridgeTrait;
    use BlockBridgeTrait;
}
