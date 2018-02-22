<?php
/**
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

/*
 * Determine wether a mail queue is active
 *
 * @param array    $args array of optional parameters<br/>
 * @return boolean true on success, false on failure
 */
function mail_userapi_qisactive(Array $args=array())
{
    extract($args);
    if(!isset($objectid)) return false; // we're lazy
    if(!isset($status)) return false;
    return $status;
}
?>