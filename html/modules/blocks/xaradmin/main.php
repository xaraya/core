<?php
/** 
 * File: $Id$
 *
 * Main blocks administration 
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
 * Blocks Functions
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