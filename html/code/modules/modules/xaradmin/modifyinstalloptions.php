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
/**
 * @return array<mixed>|void data for the template display
 */

function modules_admin_modifyinstalloptions(Array $args=array())
{
    // Security
    if (!xarSecurity::check('AdminModules')) return; 
    
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    
    if (!$installer->modulestack->size) {
        xarVar::fetch('regid', 'int', $regid, NULL, xarVar::DONT_SET);
        if(!isset($regid)) throw new Exception('Missing id of module for installation options...aborting');
        $modInfo = xarMod::getInfo($regid);
        $data['authid'] = xarSec::genAuthKey('modules');
        $data['regid'] = $modInfo['regid'];
        $data['modname'] = $modInfo['name'];
        $data['displayname'] = $modInfo['displayname'];
        return $data;
    } else {
        throw new Exception('You are not installing this module...aborting');
    }
}
