<?php
/**
 * Update configuration Xaraya core CSS
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */

/**
* Module admin function to update configuration Xaraya core CSS
*
* @author AndyV_at_Xaraya_dot_Com
* @returns true
*/
function themes_admin_corecssupdate()
{
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // Security Check
    if (!xarSecurityCheck('AdminThemes')) return;

    // params
    if (!xarVarFetch('linkoptions', 'str::', $linkoptions, '', XARVAR_NOT_REQUIRED)) return;


    // set modvars
    xarModVars::set('themes', 'csslinkoption', $linkoptions);

    xarResponse::redirect(xarModURL('themes','admin','cssconfig',array('component'=>'core')));
    // Return
    return true;
}

?>