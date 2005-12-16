<?php
/*
 * Checker if our queue definition is there
 *
 * @returns false if not there
 * @returns objectinfo if it's found
 */

function mail_adminapi_getqdef($args)
{
    extract($args);
    
    $qDef = xarModGetVar('mail','queue-definition');
    if($qDef != NULL) {
        // Modvar has a value, fetch the info
        $qdefInfo = xarModApiFunc('dynamicdata','user','getobjectinfo',array('name' => $qDef));
        if(isset($qdefInfo)) return $qdefInfo;
    }
    return false;
}
?>