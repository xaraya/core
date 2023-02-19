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
    if (!xarSecurity::check('AdminModules')) return; 
    
    // TODO: check under what conditions this is needed
//    if (!xarSec::confirmAuthKey()) return;
    xarVar::fetch('regid', 'int', $regid, NULL, xarVar::DONT_SET);
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    
    if (!$installer->installmodule($regid)) return;
}
