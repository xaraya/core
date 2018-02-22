<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */

 function modules_admin_updateinstalloptions()
{
    // Security
    if (!xarSecurityCheck('AdminModules')) return; 
    
    // TODO: check under what conditions this is needed
//    if (!xarSecConfirmAuthKey()) return;
    xarVarFetch('regid', 'int', $regid, NULL, XARVAR_DONT_SET);
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    
    if (!$installer->installmodule($regid,1)) return;
}

?>
