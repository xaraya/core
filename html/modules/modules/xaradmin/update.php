<?php

/**
 * Update a module
 *
 * @param id the module's registered id
 * @param newdisplayname the new display name
 * @param newdescription the new description
 * @returns bool
 * @return true on success, error message on failure
 */
function modules_admin_update()
{
    // Get parameters
    xarVarFetch('id','id',$regId);
    xarVarFetch('newdisplayname','str::',$newDisplayName);

    if (!xarSecConfirmAuthKey()) return;

    // Pass to API
    $updated = xarModAPIFunc('modules',
                             'admin',
                             'update',
                              array('regid' => $regId,
                                    'displayname' => $newDisplayName));
    
    if (!isset($updated)) return;
    
    xarResponseRedirect(xarModURL('modules', 'admin', 'list'));
    
    return true;
}

?>
