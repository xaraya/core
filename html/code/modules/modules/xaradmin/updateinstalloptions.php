<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
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
