<?php
/**
 * @package modules
 * @subpackage mail module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/771.html
 */

/*
 * Map a mail item to a queue based on defined rules
 *
 * @param object msg_structure parsed out result from the mailparser class
 * @returns array of queue idents
 */
function mail_adminapi_maptoqueue($args)
{
    extract($args);
    if(!isset($msg_structure)) return;

    sys::import('xaraya.structures.sequences.queue');
    // Test mapping, map em all to the masterq
    $q = new Queue('dd',array('name' => 'masterq'));
    return $q;
}
?>
