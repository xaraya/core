<?php

/**
 * display user
 */
function roles_user_display()
{
    if (!xarVarFetch('uid','int:1:',$uid)) return;

    // Get user information
    $data = xarModAPIFunc('roles',
                          'user',
                          'get',
                          array('uid' => $uid));

    if ($data == false) return;
    
    $data['email'] = xarVarPrepForDisplay($data['email']);

    $hooks = xarModCallHooks('item',
                             'display',
                             $uid,
                             xarModURL('roles',
                                       'user',
                                       'display',
                                       array('uid' => $uid)));
    if (empty($hooks)) {
        $data['hooks'] = '';
    } elseif (is_array($hooks)) {
        $data['hooks'] = join('',$hooks);
    } else {
        $data['hooks'] = $hooks;
    }

    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));

    return $data;
}

?>