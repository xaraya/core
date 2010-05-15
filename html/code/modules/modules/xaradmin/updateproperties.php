<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
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
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // Get parameters
    xarVarFetch('id','id',$regid);
    xarVarFetch('olddisplayname','str::',$olddisplayname);
    xarVarFetch('displayname','str::',$displayname);
    xarVarFetch('admincapable','isset',$admincapable, NULL, XARVAR_DONT_SET);
    xarVarFetch('usercapable','isset',$usercapable, NULL, XARVAR_DONT_SET);
    $admincapable = isset($admincapable) ? true : false;
    $usercapable = isset($usercapable) ? true : false;

    if (empty($displayname)) $displayname = olddisplayname;;

    // Pass to API
    $updated = xarMod::apiFunc('modules',
                             'admin',
                             'updateproperties',
                              array('regid' => $regid,
                                    'admincapable' => $admincapable,
                                    'usercapable' => $usercapable,
                                    'displayname' => $displayname));

    if (!isset($updated)) return;

    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);
    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } else {
        xarController::redirect(xarModURL('modules', 'admin', 'list'));
    }

    return true;
}

?>
