<?php

/**
 * Initialise a module
 *
 * Loads module admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 *
 * @param id the module id to initialise
 * @returns
 * @return
 */
function modules_admin_initialise()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    $id = xarVarCleanFromInput('id');
    if (!isset($id)) {
        $msg = xarML('No module id specified',
                    'modules');
        xarExceptionSet(XAR_USER_EXCEPTION, 
                    'MISSING_DATA',
                     new DefaultUserException($msg));
        return;
    }

    // Initialise module
    $initialised = xarModAPIFunc('modules',
                                'admin',
                                'initialise',
                                array('regid' => $id));

    // throw back exception (may be NULL or false)
    if (empty($initialised)) return;
    $minfo=xarModGetInfo($id);
    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];

    xarResponseRedirect(xarModURL('modules', 'admin', "list", array('state' => 0), NULL, $target));

    return true;
}

?>