<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * Update a module
 *
 * @author Xaraya Development Team
 * @param int id the module's registered id
 * @param string newdisplayname the new display name
 * @param bool admincapable the whether the module shows an admin menu
 * @param bool usercapable the whether the module shows a user menu
 * @return mixed true on success, error message on failure
 */
function modules_admin_updateproperties()
{
    // Security
    if (!xarSecurity::check('AdminModules')) return; 
    
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // Get parameters
    xarVar::fetch('id','id',$regid);
    xarVar::fetch('olddisplayname','str::',$olddisplayname);
    xarVar::fetch('displayname','str::',$displayname);
    xarVar::fetch('admincapable','isset',$admincapable, NULL, xarVar::DONT_SET);
    xarVar::fetch('usercapable','isset',$usercapable, NULL, xarVar::DONT_SET);
    $admincapable = isset($admincapable) ? true : false;
    $usercapable = isset($usercapable) ? true : false;

    if (empty($displayname)) $displayname = $olddisplayname;

    // Pass to API
    $updated = xarMod::apiFunc('modules',
                             'admin',
                             'updateproperties',
                              array('regid' => $regid,
                                    'admincapable' => $admincapable,
                                    'usercapable' => $usercapable,
                                    'displayname' => $displayname));

    if (!isset($updated)) return;

    xarVar::fetch('return_url', 'isset', $return_url, NULL, xarVar::DONT_SET);
    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } else {
        xarController::redirect(xarController::URL('modules', 'admin', 'list'));
    }

    return true;
}
