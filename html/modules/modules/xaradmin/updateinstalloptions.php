<?php
function modules_admin_updateinstalloptions()
{
    // TODO: check under what conditions this is needed
//    if (!xarSecConfirmAuthKey()) return;
    xarVarFetch('regid', 'int', $regid, NULL, XARVAR_DONT_SET);
	if (!xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$regid, 'phase' => 1))) return;
}

?>
