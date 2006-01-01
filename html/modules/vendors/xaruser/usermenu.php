<?php
function vendors_user_usermenu($args)
{

    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;
    extract($args);
    if(!xarVarFetch('phase','notempty', $phase, 'menu', XARVAR_NOT_REQUIRED)) {return;}
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Your Account Preferences')));
    $data = array(); $hooks = array();
    switch(strtolower($phase)) {
        case 'menu':
            $iconbasic = 'modules/roles/xarimages/home.gif';
            $iconenhanced = 'modules/roles/xarimages/home.gif';
            $current = xarModURL('roles', 'user', 'account', array('moduleload' => 'vendors'));
            $data = xarTplModule('vendors','user', 'user_menu_icon', array('iconbasic'    => $iconbasic,
                                                                         'iconenhanced' => $iconenhanced,
                                                                         'current'      => $current));
            break;
        case 'form':

		    $stub = basename(xarServerGetCurrentURL());

			switch(strtolower($stub)) {
				case 'tab1':
					// get some roles properties, might be useful
					$uname = xarUserGetVar('uname');
					$name = xarUserGetVar('name');
					$uid = xarUserGetVar('uid');
					$email = xarUserGetVar('email');
					$role = xarUFindRole($uname);
					$home = $role->getHome();
					$authid = xarSecGenAuthKey();
					$submitlabel = xarML('Submit');
					$item['module'] = 'roles';

					$hooks = xarModCallHooks('item','modify',$uid,$item);
					if (isset($hooks['dynamicdata'])) {
						unset($hooks['dynamicdata']);
					}

					$data = xarTplModule('vendors','user', 'user_menu_tab1',
										  array('authid'       => $authid,
										  'withupload'   => $withupload,
										  'name'         => $name,
										  'uname'        => $uname,
										  'home'         => $home,
										  'hooks'        => $hooks,
										  'emailaddress' => $email,
										  'submitlabel'  => $submitlabel,
										  'uid'          => $uid));
					break;

				case 'tab2':
					$data = xarTplModule('vendors','user', 'user_menu_tab2');
					break;
			}
			break;

        case 'updatebasic':

            // Confirm authorisation code.
//            if (!xarSecConfirmAuthKey()) return;

    		xarModCallHooks('item', 'update', $uid, $item);

            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'account'));
            return true;
    }
    return $data;
}
?>
