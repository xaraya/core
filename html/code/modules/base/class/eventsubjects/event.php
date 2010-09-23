<?php
/**
 * Event Messaging System 
**/
/** 
 * Event Subject
 *
 * This event is raised by the event system every time the notify method is called
 * Listeners to this event should return a bool response, true on success
 *
 * It also serves as the template from which all other event subjects should inherit
 * All subjects must implement ixarEventSubject interface
**/
/**
 * Called as xarEvent::notify('Event', array('id', 'event', 'module_id', 'eventtype'));
 * Used by xarEvent::notify() method
**/ 
sys::import('xaraya.structures.descriptor');
class BaseEventSubject extends ObjectDescriptor implements ixarEventSubject
{
    // protected $args = array();      // from descriptor    
    protected $observers = array(); // xarEvent::notify is responsible for populating this array 
    protected $subject = 'Event';   // name of this event subject
    /**
     * Constructor
     *
     * @param int $args['id'] id of real event raised
     * @param int $args['module_id'] id of module real event belongs to
     * @param string $args['event'] name of real event
     * @return void
     * @throws none
    **/     
    public function __construct($args=array())
    {

        parent::__construct($args); // $this->setArgs($args);                              
    }

    public function notify()
    {
        $result = true;
        foreach ($this->observers as $obs) {
            // observers of this event should return a bool response, true on success
            $response = $obs->notify($this);
            if (!$response) $result = false;
        }
        return $result;
    }
    
    public function attach(ixarEventObserver $observer)
    {
        $id = $observer->module;
        $this->observers[$id] = $observer;
    }
    
    public function detach(ixarEventObserver $observer)
    {
        $id = $observer->module;
        if (isset($this->observers[$id]))
            unset($this->observers[$id]);    
    }
    
    public function getSubject()
    {
        return $this->subject;
    }
}

?>