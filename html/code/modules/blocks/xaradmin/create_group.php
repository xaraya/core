<?php
/**
 * Block group management - create a new block group
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * create a new block group
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_create_group()
{
    // Get parameters
    if (!xarVarFetch('group_name', 'pre:lower:ftoken:passthru:str:1:', $name)) {return;}
    if (!xarVarFetch('group_template', 'str:1:', $template, null, XARVAR_NOT_REQUIRED)) {return;}

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // Security Check
    if(!xarSecurityCheck('AddBlock', 0, 'Instance')) {return;}

    // Check the group name has not already been used.
    $checkname = xarMod::apiFunc('blocks', 'user', 'groupgetinfo', array('name' => $name));
    if (!empty($checkname)) {
        throw new DuplicateException(array('block group',$name));
    }

    // Pass to API
    if (!xarMod::apiFunc(
        'blocks', 'admin', 'create_group',
        array('name' => $name, 'template' => $template))
    ) {return;}

    xarResponse::redirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>