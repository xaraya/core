<?php

/**
 * Deactivate a module
 *
 * Loads module admin API and calls the setstate
 * function to actually perfrom the deactivation,
 * then redirects to the list function with a status
 * message and returns true.
 *
 * @access public
 * @param id the mdoule id to deactivate
 * @returns
 * @return
 */
function modules_admin_deactivate()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return; 

    // deactivate
    $deactivated = xarModAPIFunc('modules',
                                'admin',
                                'setstate',
                                array('regid' => $id,
                                      'state' => XARMOD_STATE_INACTIVE));
    //throw back
    if (!isset($deactivated)) return;
    $minfo=xarModGetInfo($id);
    // set the target location (anchor) to go to within the page 
    $target=$minfo['name'];
    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    //    xarResponseRedirect(xarModURL('modules', 'admin', "list#$target"));
    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}

?>