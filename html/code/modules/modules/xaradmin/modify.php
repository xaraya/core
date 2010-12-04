<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
 * @return array data for the template display
 */
function modules_admin_modify(Array $args=array())
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
        $curhook = $observer;
        // get subject itemtypes this observer is hooked to (if any)
        $subjects = xarHooks::getObserverSubjects($observer, $modname);
            $hookstate = 0;
            if (!empty($subjects[$modname][0][0])) {
                // Hooked by ALL scopes to ALL itemtypes
                $hookstate = 1;
            } elseif (!empty($subjects[$modname][0])) {
                // Hooked by SOME scopes to ALL itemtypes 
                if (!empty($modinfo['scopes'])) {
                    foreach ($modinfo['scopes'] as $scope => $val) {
                        $ishooked = !empty($subjects[$modname][0][$scope]);
                        if ($ishooked) $hookstate = 2;
                        $itemtypes[0]['scopes'][$scope] = $ishooked;
                    }
                }
            } 

            if (!empty($itemtypes)) {
                // Hooked by SOME scopes to SOME itemtypes
                foreach ($itemtypes as $typeid => $itemtype) {
                    if (empty($typeid)) continue;
                    $itemtypes[$typeid]['scopes'] = array();                    
                    if ($hookstate != 0) {
                        $itemtypes[$typeid]['scopes'][0] = 0;
                        // already matched the state
                        $ishooked = false;
                    } else {
                        if (!empty($subjects[$modname][$typeid][0])) {
                            // ALL scopes this itemtype
                            $itemtypes[$typeid]['scopes'][0] = 1;
                            $newstate = 3;
                        } else {
                            if (!empty($modinfo['scopes'])) {
                                // SOME scopes this itemtype
                                foreach ($modinfo['scopes'] as $scope => $val) {
                                    $ishooked = !empty($subjects[$modname][$typeid][$scope]);
                                    if ($ishooked) {
                                        $newstate = 3;
                                        $itemtypes[$typeid]['scopes'][0] = 2;
                                    }
                                    $itemtypes[$typeid]['scopes'][$scope] = $ishooked;                 
                                }
                            }
                            if (!isset($itemtypes[$typeid]['scopes'][0]))
                                $itemtypes[$typeid]['scopes'][0] = 0;
                        }
                        
                    }
                }
                if (!empty($newstate)) { $hookstate = $newstate; unset($newstate); }        
            }
        /*
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
        */
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
