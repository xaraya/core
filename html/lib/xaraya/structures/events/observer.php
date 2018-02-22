<?php
/**
 * Event Messaging System 
 * @package core\events
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/
/** 
 * Event Observer
 *
 * This serves as the template from which all other event observers should inherit
 * All subjects must implement ixarEventSubject interface
**/
class EventObserver extends Object implements ixarEventObserver
{
    public function notify(ixarEventSubject $subject)
    {
        // observers obtain arguments from the subject
        $args = $subject->getArgs();
        // observers may, or may not return a response,
        // developers writing observers should return whatever the subject expects
    }
}

/**
 * Event Observer Interface
 *
 * All Event Observers must implement this
**/
interface ixarEventObserver
{
    public function notify(ixarEventSubject $subject);
}
?>