<?php
/**
 * Block group management - delete a block group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * delete a block group
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_delete_group()
{
    // Security Check
    if(!xarSecurityCheck('DeleteBlock', 0, 'Instance')) {return;}

    if (!xarVarFetch('gid', 'int:1:', $gid)) {return;}
    if (!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) {return;}

    // Check for confirmation
    if (empty($confirm)) {
        // No confirmation yet - get one

        // Get details on current group
        $group = xarModAPIFunc(
            'blocks', 'admin', 'groupgetinfo',
            array('blockGroupId' => $gid)
        );

        if ($group == NULL) {return;}

        return array(
            'group' => $group,
            'authid' => xarSecGenAuthKey(),
            'deletelabel' => xarML('Delete')
        );
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {return;}

    // Pass to API
    xarModAPIFunc(
        'blocks', 'admin', 'delete_group', array('gid' => $gid)
    );

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>