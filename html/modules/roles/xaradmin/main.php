<?php
/**
 * Main admin function
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * the main administration function
 */
function roles_admin_main()
{
    if (!xarSecurityCheck('EditRole')) return;

    if (xarModVars::get('modules', 'disableoverview') == 0){
        return xarTplModule('roles','admin','overview');
    } else {
        xarResponseRedirect(xarModURL('roles', 'admin', 'showusers'));
        return true;
    }
}
?>