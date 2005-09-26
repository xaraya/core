<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Update a module
 *
 * @author Xaraya Development Team
 * @param id the module's registered id
 * @param newdisplayname the new display name
 * @param admincapable the whether the module shows an admin menu
 * @param usercapable the whether the module shows a user menu
 * @returns bool
 * @return true on success, error message on failure
 */
function modules_admin_updateproperties()
{
    if (!xarSecConfirmAuthKey()) return;

    // Get parameters
    xarVarFetch('id','id',$regid);
    xarVarFetch('olddisplayname','str::',$olddisplayname);
    xarVarFetch('displayname','str::',$displayname);
    xarVarFetch('admincapable','isset',$admincapable, NULL, XARVAR_DONT_SET);
    xarVarFetch('usercapable','isset',$usercapable, NULL, XARVAR_DONT_SET);
    $admincapable = isset($admincapable) ? 1 : 0;
    $usercapable = isset($usercapable) ? 1 : 0;

    if (empty($displayname)) $displayname = olddisplayname;;

    // Pass to API
    $updated = xarModAPIFunc('modules',
                             'admin',
                             'updateproperties',
                              array('regid' => $regid,
                                    'admincapable' => $admincapable,
                                    'usercapable' => $usercapable,
                                    'displayname' => $displayname));

    if (!isset($updated)) return;

    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);
    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } else {
        xarResponseRedirect(xarModURL('modules', 'admin', 'list'));
    }

    return true;
}

?>