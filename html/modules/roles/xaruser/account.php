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

    $name = xarUserGetVar('name');
    $uid = xarUserGetVar('uid');
    $output = xarModCallHooks('item', 'usermenu', '', array());

    if (empty($output)){
        $message = xarML('There are no account options configured.');
    } elseif (is_array($output)) {
        $output = join('',$output);
    }

    if (empty($message)){
        $message = '';
    }

    return array('name' => $name, 'output' => $output, 'message' => $message);
}

?>