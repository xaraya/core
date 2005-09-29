<?php
/**
 * Load admin part of the module in question
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
 * Load admin part of the module in question
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success
 * @throws  no exceptions
 * @todo    Where is this used and why?
*/
function adminpanels_admin_loadnewmod()
{
    // Get vars
    if (!xarVarFetch('mname','str:1:',$mname,'adminpanels')) return;

    xarResponseRedirect(xarModURL($mname, 'admin', 'main'));
    return true;
}

?>