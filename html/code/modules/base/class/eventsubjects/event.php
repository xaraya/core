<?php
/**
 * Event Messaging System 
**/
/** 
 * Event Subject
 *
 * This event is raised by the event system every time the notify method is called
 * Observers of this event should return no response
**/
sys::import('xaraya.structures.events.subject');
class BaseEventSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'Event';   // name of this event subject
}

?>