<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * delete a block instance
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_delete_instance()
{
    // Get parameters
    if (!xarVarFetch('bid', 'id', $bid)) return;
    if (!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) {return;}

    // Security Check
    if (!xarSecurityCheck('DeleteBlock', 0, 'Instance')) {return;}

    // Check for confirmation
    if (empty($confirm)) {
        // No confirmation yet - get one

        // Get details on current block
        $blockinfo = xarModAPIFunc(
            'blocks', 'user', 'get', array('bid' => $bid)
        );

        return array(
            'instance' => $blockinfo,
            'authid' => xarSecGenAuthKey(),
            'deletelabel' => xarML('Delete')
        );
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {return;}

    // Pass to API
    xarModAPIFunc(
        'blocks', 'admin', 'delete_instance',
        array('bid' => $bid)
    );

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));

    return true;
}

?>