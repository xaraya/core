<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/68.html
 */
sys::import('xaraya.structures.events.observer');
/**
 * Event Subject Observer
 *
 * Event Subject is notified every time xarEvents::notify is called
 * see /code/modules/eventsystem/class/eventsubjects/event.php for subject info 
 *
 * This observer is responsible for logging the event to the system log
**/
class BaseEventObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'base';
    
    /**
     * Notify
     * 
     * @param ixarEventSubject $subject
     */
    public function notify(ixarEventSubject $subject)
    {
        $args = $subject->getArgs();
        if (!empty($args['event'])) {
            xarLog::message("xarEvents::notify: notified $args[event] subject observers");
        }
    }
}
?>