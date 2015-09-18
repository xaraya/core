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