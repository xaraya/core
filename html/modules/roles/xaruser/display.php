<?php
/**
 * display user
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
 * display user
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_user_display($args)
{
    extract($args);

    if (!xarVarFetch('uid','int:1:',$uid, xarUserGetVar('uid'))) return;

    // Get user information
    $data = xarModAPIFunc('roles',
                          'user',
                          'get',
                          array('uid' => $uid));

    if ($data == false) return;
    
    $data['email'] = xarVarPrepForDisplay($data['email']);

    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = 0; // handle groups differently someday ?
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('uid' => $uid));

    $hooks = array();
    $hooks = xarModCallHooks('item', 'display', $uid, $item);
    $data['hooks'] = $hooks;

    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));

    return $data;
}

?>