<?php
/**
 * Block Functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * Blocks Functions
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_main()
{

// Security Check
    if(!xarSecurityCheck('EditBlock')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        // Return the output
        return array();
    } else {
        xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));
    }
    // success
    return true;
}

?>