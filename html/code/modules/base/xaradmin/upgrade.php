<?php
/**
 *  View recent extension releases
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * 
 *
 * @author John Cox
 * @access public
 * @return array data for the template display
 * @todo change feed url once release module is moved
 */
function base_admin_upgrade()
{
    // Security
    if(!xarSecurityCheck('AdminBase')) return;
    
    $fileversion = XARCORE_VERSION_NUM;
    $dbversion = xarConfigVars::get(null, 'System.Core.VersionNum');
    sys::import('xaraya.version');
    $data['versioncompare'] = xarVersion::compare($fileversion, $dbversion);
    return $data;
}
?>
