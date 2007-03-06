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
 * updaterole - update a role
 * this is an action page
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_updaterole()
{
    if (!xarSecConfirmAuthKey()) return;
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('itemtype', 'int', $itemtype, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str', $returnurl, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('duvs','array',$duvs,array(),XARVAR_NOT_REQUIRED)) return;
    foreach($duvs as $key => $value) xarModSetUserVar('roles',$key, $value, $uid);

    $basetype = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));

    $object = DataObjectMaster::getObject(array('module'   => 'roles',
                                                'itemtype' => $basetype));
    $object->getItem(array('itemid' => $uid));

    //Save the old state and type
    $oldrole = xarRoles::getRole($uid);
    $oldstate = $oldrole->getState();
    $oldtype = $oldrole->getType();
    $oldpass = $oldrole->getPass();

    $isvalid = $object->checkInput();

    $puname = $object->properties['uname']->value;
    $pname  = $object->properties['name']->value;

    // TODO: what about the role itemtype?
    if ($basetype == ROLES_USERTYPE) {
        $pemail = $object->properties['email']->value;
        $pstate = $object->properties['state']->value;
        $pass   = $object->properties['password']->value;

        $newpass = md5($pass);
        $passchanged = false;
        if ($oldpass != $newpass) {
            $passchanged = true;
            $object->properties['password']->value = $newpass;
        }

        if (!xarVarFetch('allowemail','checkbox',$allowemail,false,XARVAR_NOT_REQUIRED)) return;


        // check for duplicate username
        $user = xarModAPIFunc('roles','user','get',array('uname' => $puname));

        if (($user != false) && ($user['uid'] != $uid)) {
            throw new DuplicateException(array('user',$puname));
        }

       //the user cannot receive emails from other users until they allow it and admin allows this option
       xarModSetUserVar('roles','usersendemails', $allowemail, $uid);
    }

    $object->updateItem();

    //Change the defaultgroup var values if the name is changed
    if ($basetype == ROLES_GROUPTYPE) {
        $defaultgroupuid = xarModAPIFunc('roles','user','get',
                                                     array('uname'  => xarModGetVar('roles','defaultgroup'),
                                                           'type'   => ROLES_GROUPTYPE));
        if ($uid == $defaultgroupuid) xarModSetVar(xarModGetNameFromID(xarModGetVar('roles','defaultauthmodule')), 'defaultgroup', $pname);

        // Adjust the user count if necessary
        if ($oldtype == ROLES_USERTYPE) $oldrole->adjustParentUsers(-1);
    }else {
        // Adjust the user count if necessary
        if ($oldtype == ROLES_GROUPTYPE) $oldrole->adjustParentUsers(1);
        //TODO : Be able to send 2 email if both password and type has changed... (or an single email with a overall msg...)
        //Ask to send email if the password has changed
        $pass = $object->properties['password']->value;
        if ($passchanged) {
            if (xarModGetVar('roles', 'askpasswordemail')) {
                xarSessionSetVar('tmppass',$pass);
                xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                array('uid' => array($uid => '1'), 'mailtype' => 'password')));
            }
            //TODO : If askpasswordemail is false, the user won't know his new password...
        }
        //Ask to send email if the state has changed
        if ($oldstate != $pstate) {
            //Get the notice message
            switch ($pstate) {
                case ROLES_STATE_INACTIVE :
                    $mailtype = 'deactivation';
                break;
                case ROLES_STATE_NOTVALIDATED :
                    $mailtype = 'validation';
                break;
                case ROLES_STATE_ACTIVE :
                    $mailtype = 'welcome';
                break;
                case ROLES_STATE_PENDING :
                    $mailtype = 'pending';
                break;
            }
            if (xarModGetVar('roles', 'ask'.$mailtype.'email')) {
                xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                              array('uid' => array($uid => '1'), 'mailtype' => $mailtype)));
            }
            //CHECKME : If ask$mailtypeemail is false, the user won't know his new state...
        }
    }

    if (empty($returnurl)) {
        xarResponseRedirect(xarModURL('roles', 'admin', 'modifyrole', array('itemtype' => $itemtype,
                                                                            'uid' => $uid)));
    } else {
        xarResponseRedirect($returnurl);
    }
}

?>
