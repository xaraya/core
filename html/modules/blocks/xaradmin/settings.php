<?php
/**
 * List modules and current settings
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * List modules and current settings
 * @param several params from the associated form in template
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('EditBlock')) return;

    if (!xarVarFetch('selstyle', 'str:1:', $selstyle, 'plain', XARVAR_NOT_REQUIRED)) return; 
    
    xarModSetVar('blocks', 'selstyle', $selstyle);
    
    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));

    return true;
}

?>