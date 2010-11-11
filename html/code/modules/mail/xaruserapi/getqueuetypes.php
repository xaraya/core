<?php
/**
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * @param array   $args array of parameters
 * Return a list of queue types in a structured format, also suitable for dd validation
   *
   */
function mail_userapi_getqueuetypes(Array $args=array())
{
    // We do this here because this way the queue types are translateable
    // and we can easily add qTypes later (like a /dev/null like one) without
    // the definition object changing.
    // This function is called by dd on the validation of the type property 
    // of the queues object we are using.
    $qTypes[1] = xarML('Incoming mail');
    $qTypes[2] = xarML('Outgoing mail');
    $qTypes[3] = xarML('Demote  (black hole)');
    $qTypes[4] = xarML('Promote (redispatch)');
    return $qTypes;
}
?>