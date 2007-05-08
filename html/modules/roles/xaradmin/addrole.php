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
 * Create a role
 *
 * This function tries to create a user and provides feedback on the
 * result.
 *
 * @author Jan Schrage, Marc Lutolf
 */
function roles_admin_addrole()
{
    if (!xarSecConfirmAuthKey()) return;

    // get common vars
    if (!xarVarFetch('pparentid',  'int', $pparentid,  NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'isset',  $return_url, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('itemtype', 'int', $itemtype, ROLES_USERTYPE, XARVAR_NOT_REQUIRED)) return;
    // TODO: need to see what to do with auth module
    $basetype = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));

    $object = DataObjectMaster::getObject(array('module'   => 'roles',
                                                'itemtype' => $basetype));

    $isvalid = $object->checkInput();
    // @todo add preview?
    if (!$isvalid) {
        $data = xarModAPIFunc('roles','admin','menu');
        $data['itemtype'] = $itemtype;
        $data['basetype'] = $basetype;
        $data['pparentid'] = $pparentid;
        $data['authid'] = xarSecGenAuthKey();
        $data['return_url'] = $return_url;
        $data['object'] = & $object;

        //$data['preview'] = $preview;
        $item = array();
        $item['module'] = 'roles';
        $item['itemtype'] = $itemtype;
        $data['hooks'] = xarModCallHooks('item','new','',$item);
        return xarTplModule('roles','admin','newrole', $data);
    }

    if ($basetype == ROLES_USERTYPE) {
        // check for duplicate username
        $uname = $object->properties['uname']->value;
        $email = $object->properties['email']->value;
        $user = xarModAPIFunc('roles', 'user','get',
                        array('uname' => $uname));

        if ($user) {
            throw new DuplicateException(array('user',$uname));
        }

        // check for duplicate email address
        if(xarModGetVar('roles','uniqueemail')) {
            $user = xarModAPIFunc('roles','user', 'get', array('email' => $email));
            if ($user) throw new DuplicateException(array('email',$email));
        }
        $object->properties['password']->value = md5($object->properties['password']->value);
    }


    $uid = $object->createItem();
    if (empty($uid)) return;

    $args = array('uid' => $uid, 'gid' => $pparentid);
    if (!xarModAPIFunc('roles','admin','addmember',$args)) return;

    if (!xarVarFetch('duvs','array',$duvs,array(),XARVAR_NOT_REQUIRED)) return;
    foreach($duvs as $key => $value) {
        xarModSetUserVar('roles',$key, $value, $uid);
    }
    xarModSetUserVar('roles','usersendemails', false, $uid);

    // call item create hooks
    // TODO: move to createItem() function
    $item['module'] = 'roles';
    $item['itemtype'] = $itemtype;
    $item['itemid'] = $uid;
    xarModCallHooks('item', 'create', $uid, $item);

    // redirect to the next page
    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } else {
        xarResponseRedirect(xarModURL('roles', 'admin', 'modify',array('itemtype' => $itemtype,
                                                                           'uid' => $uid)));
    }
}
?>
