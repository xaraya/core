<?php
/**
 * Display user
 *
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 */
/**
 * Display user
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @param int uid
 * @return array
 */
function roles_user_display($args)
{
    extract($args);

    if (!xarVarFetch('uid','id',$uid, xarUserGetVar('uid'))) return;
    if (!xarVarFetch('itemid', 'int', $itemid, NULL, XARVAR_DONT_SET)) return;

    $uid = isset($itemid) ? $itemid : $uid;

    // Get role information
    $data = xarModAPIFunc('roles', 'user', 'get',
                    array('itemid' => $uid,
                          'itemtype' => $itemtype));

    if ($data == false) return;

    $data['email'] = xarVarPrepForDisplay($data['email']);

    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['type'];
    $item['itemid']= $uid;
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('uid' => $uid));
    $data['hooks'] = xarModCallHooks('item', 'display', $uid, $item);

    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));

    return $data;
}

?>