<?php

/**
 * Events available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarEvents;

/**
 * For documentation purposes only - available via EventsTrait
 */
interface EventsInterface extends WrapperInterface
{
    public const SLICE = 'events';
}

/**
 * Events available via methods
 */
trait EventsTrait
{
    use WrapperTrait;
}

/**
 * Access xarEvents::* methods (notify, ...)
 *
 * Available methods:
 * - notify()
 * - ...
 */
class EventsService implements EventsInterface
{
    use EventsTrait;
}
