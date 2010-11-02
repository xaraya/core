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
 * Checker if our queue definition is there
 *
 * @returns false if not there
 * @returns objectinfo if it's found
 */

function mail_adminapi_getqdef($args)
{
    extract($args);

    $qDef = xarModVars::get('mail','queue-definition');
    if($qDef != NULL) {
        // Modvar has a value, fetch the info
        $qdefInfo = DataObjectMaster::getObjectInfo(array('name' => $qDef));
        if(isset($qdefInfo)) return $qdefInfo;
    }
    return false;
}
?>