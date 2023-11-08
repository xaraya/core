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
 * Checker if our queue definition is there
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @return mixed objectinfo if it's found, false if not there
 */

function mail_adminapi_getqdef(Array $args=array())
{
    extract($args);

    $qDef = xarModVars::get('mail','queue-definition');
    if($qDef != NULL) {
        // Modvar has a value, fetch the info
        $qdefInfo = DataObjectFactory::getObjectInfo(array('name' => $qDef));
        if(isset($qdefInfo)) return $qdefInfo;
    }
    return false;
}
