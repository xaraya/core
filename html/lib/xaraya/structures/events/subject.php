<?php
/**
 * Event Messaging System 
**/
/** 
 * Event Subject
 *
 * This serves as the template from which all other event subjects should inherit
 * All subjects must implement ixarEventSubject interface
**/
abstract class EventSubject extends Object implements ixarEventSubject
{
    protected $args;                // args passed from caller when event is raised
    protected $observers = array(); // xarEvents::notify is responsible for populating this array
    protected $subject = 'Event';   // name of this event subject

    /**
     * Constructor
     * overloading is optional
     *
     * @param mixed $args, determined by the subject, default null
     * 
     * $args are passed to EMS notify() method by caller, eg, as, notify('Event', $args)
     * and from notify method to this object in the constructor
     * @return void
     * @access public
     * @throws none
    **/     
    public function __construct($args=null)
    {
        $this->setArgs($args);                           
    }
    /**
     * notify method
     * This function is required by the EMS system,
     * overloading is optional
     *
     * @param none
     * @return void
     * @access public
     * @throws none
    **/
    public function notify()
    {
        // in its most basic form this function returns no output,
        // it just loops through attached observers and notifies them
        // event subjects are permitted to modify this behaviour,
        // the EMS notify() method will return the response from this function to the event caller
        foreach ($this->observers as $obs) {
            try {
                $obs->notify($this);
            } catch (Exception $e) {
                if (xarEvents::$debug) throw $e;
                // events should never fail, ever!                
                continue;
            }
        }
    }
    /**
     * attach method
     * This function is required by the EMS system,
     * and cannot be overloaded
     *
     * @param ixarEventObserver object
     * @return void
     * @access public
     * @throws none
    **/    
    final public function attach(ixarEventObserver $obs)
    {
        $id = $obs->module;
        $this->observers[$id] = $obs;
    }
    /**
     * detach method
     * This function is required by the EMS system,
     * and cannot be overloaded
     *
     * @param ixarEventObserver object
     * @return void
     * @access public
     * @throws none
    **/      
    final public function detach(ixarEventObserver $obs)
    {
        $id = $obs->module;
        if (isset($this->observers[$id]))
            unset($this->observers[$id]);    
    }
    /**
     * get subject method
     * This function is required by the EMS system,
     * and cannot be overloaded
     *
     * @param none
     * @return string, name of subject
     * @access public
     * @throws none
    **/    
    final public function getSubject()
    {
        return $this->subject;
    }
    
    public function getArgs()
    {
        return $this->args;
    }
    
    public function setArgs($args)
    {
        if (empty($this->args) || !is_array($args)) {
            $this->args = $args;
        } elseif (is_array($this->args) && is_array($args)) {
            foreach ($args as $k => $v) 
                if (isset($v)) $this->args[$k] = $v;
        }
    }

}

interface ixarEventSubject
{
    public function notify();
    public function attach(ixarEventObserver $obs);
    public function detach(ixarEventObserver $obs);
    public function getSubject();
    public function getArgs();
    public function setArgs($args);
}

?>