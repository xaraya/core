<?php
/**
 * Block group management - create a new block group
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
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
    if (!xarVarFetch('group_template', 'str:1:', $template, '', XARVAR_NOT_REQUIRED)) {return;}

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {return;}

    // Security Check
    if(!xarSecurityCheck('AddBlock', 0, 'Instance')) {return;}

    // Check the group name has not already been used.
    $checkname = xarModAPIfunc('blocks', 'user', 'groupgetinfo', array('name' => $name));
    if (!empty($checkname)) {
        $msg = xarML('Block group name "#(1)" already exists', $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Pass to API
    if (!xarModAPIFunc(
        'blocks', 'admin', 'create_group',
        array('name' => $name, 'template' => $template))
    ) {return;}

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>