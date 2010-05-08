<?php
/**
 *  View recent extension releases
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * View recent module releases via central repository
 *
 * @author John Cox
 * @access public
 * @param none
 * @return array Information of recent releases from http://www.xaraya.com/
 * @todo change feed url once release module is moved
 */
function base_admin_upgrade()
{
    // Security Check
    if(!xarSecurityCheck('AdminBase')) return;
    
    $fileversion = XARCORE_VERSION_NUM;
    $dbversion = xarConfigVars::get(null, 'System.Core.VersionNum');
    sys::import('xaraya.version');
    $data['versioncompare'] = xarVersion::compare($fileversion, $dbversion);
    return $data;
}
?>