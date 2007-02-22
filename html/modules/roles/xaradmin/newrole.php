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
 * Show new role form
 *
 * @author Marc Lutolf
 * @author Johnny Robeson
 */
function roles_admin_newrole()
{
    $data = array();
    $defaultRole = xarModAPIFunc('roles', 'user', 'get', array('name'  => xarModGetVar('roles','defaultgroup'), 'type'   => 1));

    $defaultuid = $defaultRole['uid'];
    if (!xarVarFetch('return_url',  'isset', $data['return_url'], NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('pparentid', 'int:', $data['pparentid'], $defaultuid, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pname',       'str:1:', $data['pname'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'int',    $itemtype, ROLES_USERTYPE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('puname',      'str:1:35:', $data['puname'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pemail',      'email', $data['pemail'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ppass1',      'str:1:', $data['ppass1'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state',       'int:1:', $data['pstate'], 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('duvs', 'array', $data['duvs'], array(), XARVAR_NOT_REQUIRED)) return;

    $data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
    $types = xarModAPIFunc('roles','user','getitemtypes');
    $data['itemtype'] = $itemtype;
    $data['itemtypename'] = $types[$itemtype]['label'];

    // Security Check
    if (!xarSecurityCheck('AddRole')) return;

    $data['states'] = array(ROLES_STATE_INACTIVE => xarML('Inactive'),
                            ROLES_STATE_NOTVALIDATED => xarML('Not Validated'),
                            ROLES_STATE_ACTIVE => xarML('Active'),
                            ROLES_STATE_PENDING => xarML('Pending'));
    // call item new hooks (for DD etc.)
    $item = $data;
    $item['module'] = 'roles';
    $data['hooks'] = xarModCallHooks('item', 'new', '', $item);

    $data['authid'] = xarSecGenAuthKey();
    $data['uid'] = 0;

    return $data;
}
?>
