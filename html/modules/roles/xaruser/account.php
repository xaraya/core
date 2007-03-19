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
 * Displays the dynamic user menu.
 *
 * Currently does not work, due to design
 * of menu not in place, and DD not in place.
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @todo   Finish this function.
 */
function roles_user_account()
{
    if(!xarVarFetch('moduleload','str', $data['moduleload'], '', XARVAR_NOT_REQUIRED)) {return;}

    //let's make sure other modules that refer here get to a default and existing login or logout form
    $defaultauthdata      = xarModAPIFunc('roles','user','getdefaultauthdata');
    $defaultauthmodname   = $defaultauthdata['defaultauthmodname'];
    $defaultloginmodname  = $defaultauthdata['defaultloginmodname'];
    $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];

    if (!xarUserIsLoggedIn()){
        xarResponseRedirect(xarModURL($defaultloginmodname,'user','showloginform'));
    }

    $data['uid']          = xarUserGetVar('uid');
    $data['name']         = xarUserGetVar('name');
    $data['logoutmodule'] = $defaultlogoutmodname;
    $data['loginmodule']  = $defaultloginmodname;
    $data['authmodule']   = $defaultauthmodname;
    if ($data['uid'] == XARUSER_LAST_RESORT) {
        $data['message'] = xarML('You are logged in as the last resort administrator.');
    } else  {
        $data['current'] = xarModURL('roles', 'user', 'display', array('uid' => xarUserGetVar('uid')));

        $output = array();
        $item = array();
        $item['module'] = 'roles';
        $item['itemtype'] = ROLES_USERTYPE;
        $output = xarModCallHooks('item', 'usermenu', '', array('module' => 'roles'));

        if (empty($output)){
            $message = xarML('There are no account options configured.');
        }
        $data['output'] = $output;

        if (empty($message)){
            $data['message'] = '';
        }
    }
    return $data;
}

?>
