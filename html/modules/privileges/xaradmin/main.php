<?php
/**
 * Main administration function
 *
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * the main administration function - pass-thru
 */
function privileges_admin_main()
{

// Security Check
    if(!xarSecurityCheck('ViewPrivileges')) return;

    xarResponseRedirect(xarModURL('privileges', 'admin', 'viewprivileges'));

    // success
    return true;

}

?>
