<?php
/**
 * Update configuration Xaraya core CSS
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */

/**
* Module admin function to update configuration Xaraya core CSS
*
* @author AndyV_at_Xaraya_dot_Com
 * @return boolean|string|void true on success, false on failure
*/
function themes_admin_corecssupdate()
{
    // Security
    if (!xarSecurity::check('AdminThemes')) return;

    // Confirm authorisation code
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }  
    
    // params
    if (!xarVar::fetch('linkoptions', 'str::', $linkoptions, '', xarVar::NOT_REQUIRED)) return;

    // set modvars
    xarModVars::set('themes', 'csslinkoption', $linkoptions);

    xarController::redirect(xarController::URL('themes','admin','cssconfig',array('component'=>'core')));
    // Return
    return true;
}
