<?php

/**
 * display user
 */
function roles_user_display($args)
{
    // Get parameters from whatever input we need
    $uid = xarVarCleanFromInput('uid');

    extract($args);

    // Get user information
    $data = xarModAPIFunc('roles',
                          'user',
                          'get',
                          array('uid' => $uid));

    if ($data == false) return;

    // obfuscate email address
    $data['email'] = preg_replace('/@/',' AT ',$data['email']);

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

    xarTplSetPageTitle(xarModGetVar('themes', 'SiteName').' :: '.
                       xarVarPrepForDisplay(xarML('Users'))
               .' :: '.xarVarPrepForDisplay($data['name']));

    return $data;
}

?>