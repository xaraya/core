<?php

/**
 * Remove a module
 *
 * Loads module admin API and calls the remove function
 * to actually perform the removal, then redirects to
 * the list function with a status message and retursn true.
 *
 * @access public
 * @param  id the module id
 * @returns mixed
 * @return true on success
 */
function modules_admin_remove()
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

    // Remove module
    $removed = xarModAPIFunc('modules',
                            'admin',
                            'remove',
                            array('regid' => $id));
        
    //throw back
    if(!isset($removed)) return;

    xarResponseRedirect(xarModURL('modules', 'admin', 'list'));

    return true;
}

?>