<?php
/**
 * Event Subject Observer
 *
 * Event Subject is notified every time xarEvents::notify is called
 * see /code/modules/eventsystem/class/eventsubjects/event.php for subject info 
 *
 * This observer is responsible for logging the event to the system log
**/
sys::import('xaraya.structures.events.observer');
class BaseEventObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'base';
    public function notify(ixarEventSubject $subject)
    {
        $args = $subject->getArgs();
        if (!empty($args['event'])) {
            xarLogMessage("xarEvents::notify: notified $args[event] subject observers");
        }
    }
}
?>