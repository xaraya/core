<?php

/**
 * Displays the dynamic user menu.  Currently does not work, due to design
 * of menu not in place, and DD not in place.
 * @todo    Finish this function.
 */
function roles_user_account()
{
    if (!xarUserIsLoggedIn()){
        xarResponseRedirect(xarModURL('roles',
                                      'user',
                                      'register'));
    }

    $data['name'] = xarUserGetVar('name');
    $data['uid'] = xarUserGetVar('uid');

    $output = xarModCallHooks('item', 'usermenu', '', array('module' => 'roles'));

    if (empty($output)){
        $message = xarML('There are no account options configured.');
    } elseif (is_array($output)) {
        $output = join('',$output);
    }
    $data['output'] = $output;

    if (empty($message)){
        $data['message'] = '';
    }

    return $data;
}

?>
