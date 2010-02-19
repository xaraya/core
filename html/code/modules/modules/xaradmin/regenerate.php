<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Regenerate list of available modules
 *
 * Loads module admin API and calls the regenerate function
 * to actually perform the regeneration, then redirects
 * to the list function with a status meessage and returns true.
 *
 * @author Xaraya Development Team
 * @access public
 * @param none
 * @returns bool
 * @
 */
function modules_admin_regenerate()
{
    // Security check
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // Regenerate modules
    $regenerated = xarMod::apiFunc('modules', 'admin', 'regenerate');

    if (!isset($regenerated)) return;

    // Redirect
    xarResponse::redirect(xarModURL('modules', 'admin', 'list'));

    return true;
}

?>
