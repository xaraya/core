<?php
function modules_admin_modifyinstalloptions($args)
{
    xarVarFetch('regid', 'int', $regid, NULL, XARVAR_DONT_SET);
    if(!isset($regid)) throw new Exception('Missing id of module for installation options...aborting');
    $modInfo = xarModGetInfo($regid);
    $data['authid'] = xarSecGenAuthKey('modules');
    $data['regid'] = $modInfo['regid'];
    $data['modname'] = $modInfo['name'];
    $data['displayname'] = $modInfo['displayname'];
    return $data;
}

?>
