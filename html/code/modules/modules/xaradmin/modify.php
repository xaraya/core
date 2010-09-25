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
 * Modify module settings
 *
 * This function queries the database for
 * the module's information and then queries
 * for any hooks that the module could use
 * and passes the data to the template.
 *
 * @author Xaraya Development Team
 * @param id registered module id
 * @param return_url optional return URL after updating the hooks
 * @returns array
 * @return an array of variables to pass to the template
 */
function modules_admin_modify($args)
{
    
    extract($args);

    // xarVarFetch does validation if not explicitly set to be not required
    if (!xarVarFetch('id', 'int:1', $id, 0, XARVAR_NOT_REQUIRED)) return; 
    if (empty($id)) return xarResponse::notFound();
    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);

    $modInfo = xarMod::getInfo($id);
    if (!isset($modInfo)) return;

    $modname     = $modInfo['name'];
    $displayName = $modInfo['displayname'];

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"$modname::$id")) return;

    // Get the list of all item types for this module (if any)
    try {
        $itemtypes = xarMod::apiFunc($modname,'user','getitemtypes',array());
    } catch ( FunctionNotFoundException $e) {
        $itemtypes = array();
        // No worries
    }

    // Get list of hook module(s) (observers) and the available hooks supplied 
    $observers = xarHooks::getObserverModules(); 
    foreach ($observers as $observer => $modinfo) {
        // get subject itemtypes this observer is hooked to (if any)
        $subjects = xarHooks::getObserverSubjects($observer, $modname);
        $hookstate = !empty($subjects[$modname][0]);
        if (!empty($itemtypes)) {
            foreach ($itemtypes as $key => $itemtype) {
                if ($hookstate == 1) {
                    // hooked to all itemtypes
                    $ishooked = false;
                } else {
                    // otherwise see if hooked to some
                    $ishooked = !empty($subjects[$modname][$key]);
                }
                // set hook state to some if not hooked to all                    
                if ($hookstate != 1 && $ishooked) 
                    $hookstate = 2;
                // add ishooked value to itemtype                     
                $itemtypes[$key]['ishooked'] = $ishooked;
            }
        }
        $observers[$observer]['hookstate'] = $hookstate;
        $observers[$observer]['itemtypes'] = $itemtypes;
    }
    
    $data['id'] = $id;
    $data['observers'] = $observers;
    $data['module'] = $modname;
    $data['displayname'] = $displayName;
    $data['authid'] = xarSecGenAuthKey('modules');

    if (!empty($return_url)) {
        $data['return_url'] = $return_url;
    }
    return $data;
}

?>