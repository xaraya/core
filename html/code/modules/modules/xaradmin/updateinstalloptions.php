<?php
function modules_admin_updateinstalloptions()
{
    // TODO: check under what conditions this is needed
//    if (!xarSecConfirmAuthKey()) return;
    xarVarFetch('regid', 'int', $regid, NULL, XARVAR_DONT_SET);
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    
    if (!$installer->installmodule($regid,1)) return;
}

?>
