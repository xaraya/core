<?php
/**
 * Update configuration Xaraya core CSS
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/70.html
 */

/**
* Module admin function to update configuration Xaraya core CSS
*
* @author AndyV_at_Xaraya_dot_Com
 * @return boolean true on success, false on failure
*/
function themes_admin_corecssupdate()
{
    // Security
    if (!xarSecurityCheck('AdminThemes')) return;

    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }  
    
    // params
    if (!xarVarFetch('linkoptions', 'str::', $linkoptions, '', XARVAR_NOT_REQUIRED)) return;

    // set modvars
    xarModVars::set('themes', 'csslinkoption', $linkoptions);

    xarController::redirect(xarModURL('themes','admin','cssconfig',array('component'=>'core')));
    // Return
    return true;
}

?>
