<?php
/**
 * Configure sort order in admin menu by group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
 */

/**
 * Configure sort order in admin menu by group
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    stub atm, needs to be completed
*/
function adminpanels_admin_config_bygroup()
{
    // Get vars

    // redirect back to adminpanels configuration
    xarResponseRedirect(xarModURL('adminpanels', 'admin', 'modifyconfig'));
    return true;
}

?>