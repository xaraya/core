<?php

/**
 * Activate a module
 *
 * Loads module admin API and calls the activate
 * function to actually perform the activation,
 * then redirects to the list function with a
 * status message and returns true.
 *
 * @param id the module id to activate
 * @returns
 * @return
 */
function modules_admin_activate()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    $id = xarVarCleanFromInput('id');
    if (empty($id)) {
        $msg = xarML('No module id specified',
                    'modules');
        xarExceptionSet(XAR_USER_EXCEPTION, 
                    'MISSING_DATA',
                     new DefaultUserException($msg));
        return;
    }

    // Activate
    $activated = xarModAPIFunc('modules',
                              'admin',
                              'setstate',
                              array('regid' => $id,
                                    'state' => XARMOD_STATE_ACTIVE));

    //throw back
    if (!isset($activated)) return;
    $minfo=xarModGetInfo($id);
    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];

    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}

?>