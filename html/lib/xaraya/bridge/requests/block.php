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
 * Handle Block requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */
class BlockRequest extends BasicRequest implements BlockBridgeInterface
{
    use BlockBridgeTrait;
}
