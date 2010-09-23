<?php
/**
 * Event Subject Observer
 *
 * Event Subject is notified every time xarEvent::notify is called
 * see /code/modules/eventsystem/class/eventsubjects/event.php for subject info 
 *
 * This observer is responsible for logging the event to the system log
**/
sys::import('xaraya.structures.descriptor');
class BaseEventObserver extends ObjectDescriptor implements ixarEventObserver
{
    public $module = 'base';
    public function notify(ixarEventSubject $subject)
    {
        $args = $subject->getArgs();
        if (!empty($args['event'])) {
            xarLogMessage("xarEvent::notify: notified $args[event] subject observers");
        }
        return true;
    }
}
?>