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
 * Register New Block Type
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_update_type_info()
{
    // Security Check
    // FIXME: not sure what the security check should be?
    if (!xarSecurityCheck('AdminBlock', 0, 'Instance')) {return;}

    // Get parameters
    if (!xarVarFetch('modulename', 'str:1:', $modulename, 'base', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('blocktype', 'str:1:', $blocktype, '', XARVAR_NOT_REQUIRED)) {return;}

    xarModAPIfunc(
        'blocks', 'admin', 'update_type_info',
        array('module' => $modulename, 'type' => $blocktype)
    );

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_types'));
}

?>