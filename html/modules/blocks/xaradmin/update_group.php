<?php
/** 
 * File: $Id$
 *
 * Update a block group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * update a block group
 */
function blocks_admin_update_group()
{
    // Get parameters
    if (!xarVarFetch('gid','int:1:',$gid)) return;
    if (!xarVarFetch('authid','str:1:',$authid)) return;
    if (!xarVarFetch('group_instance_order','str:1:',$group_instance_order,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('group_name','str:1:',$name)) return;
    if (!xarVarFetch('group_template','str:1:',$template,'',XARVAR_NOT_REQUIRED)) return;

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

    // Security Check
	if(!xarSecurityCheck('EditBlock',0,'Instance')) return;

    // Pass to API
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'update_group', array('id' => $gid,
                                             'template' => $template,
                                             'name' => $name,
                                             'instance_order' => $group_instance_order))) return;
 
    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>
