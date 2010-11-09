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

/*
 * Determine wether a mail queue is active
 *
 * @param $qInfo in args as returned by getobjectinfo of dd
 * @return boolean true on success, false on failure
 */
function mail_userapi_qisactive($args)
{
    extract($args);
    if(!isset($objectid)) return false; // we're lazy
    if(!isset($status)) return false;
    return $status;
}
?>