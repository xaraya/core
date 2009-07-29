<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * View the current groups
 */
function roles_admin_viewroles()
{
    // Clear Session Vars
    xarSessionDelVar('roles_statusmsg');
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;

    sys::import('modules.roles.xartreerenderer');
    $renderer = new xarTreeRenderer();
    // Load Template
    $data['authid'] = xarSecGenAuthKey();
    $data['tree']   = $renderer->drawtree($renderer->maketree());
    return $data;
}
?>
