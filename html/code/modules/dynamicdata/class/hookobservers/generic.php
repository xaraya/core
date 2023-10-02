<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\HookObservers;

use HookObserver;
use ixarEventSubject;
use ixarHookSubject;
use sys;

sys::import('xaraya.structures.hooks.observer');

/**
 * DataObject Hook Observer for Item* and Module* ixarHookSubject events
 * Notified if DD module is hooked to a particular module, itemtype and/or scope
 */
class DataObjectHookObserver extends HookObserver
{
    public $module = 'dynamicdata';

    /**
     * @param ixarHookSubject $subject
     */
    public function notify(ixarEventSubject $subject)
    {
        return static::run($subject->getArgs());
    }

    /**
     * @param array<string, mixed> $args array of optional parameters<br/>
     *        ingeger  $args['objectid'] ID of the object<br/>
     *        string   $args['extrainfo'] extra information
     * @return array<mixed> true on success, false on failure
     */
    public static function run(array $args = [])
    {
        return $args['extrainfo'] ?? [];
    }
}
