<?php
/**
 *  View recent extension releases
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */
/**
 * Function to upgrade module
 *
 * @author John Cox
 *
 * @param void N/A
 * @return array Data for the template display
 */
function base_admin_upgrade()
{
    /**
     * Pending
     * @todo change feed url once release module is moved
     */
    // Security
    if(!xarSecurityCheck('AdminBase')) return;
    
    $fileversion = xarCore::VERSION_NUM;
    $dbversion = xarConfigVars::get(null, 'System.Core.VersionNum');
    sys::import('xaraya.version');
    $data['versioncompare'] = xarVersion::compare($fileversion, $dbversion);
    return $data;
}
?>
