<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Configure hooks by hook module
 *
 * @author Xaraya Development Team
 * @param $args['curhook'] current hook module (optional)
 * @param $args['return_url'] URL to return to after updating the hooks (optional)
 * @return array data for the template display
 *
 */
function modules_admin_hooks(Array $args=array())
{
    // Security
    if(!xarSecurityCheck('ManageModules')) return;

    if (!xarVarFetch('hook', 'isset', $curhook, null, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('layout', 'pre:trim:lower:enum:bycat', $layout, 'bycat', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'str', $return_url, '', XARVAR_NOT_REQUIRED)) return;
    extract($args);
    
    // Get list of hook module(s) (observers) and the available hooks supplied 
    $hookmods = xarHooks::getObserverModules($curhook);

    if (!empty($curhook) && isset($hookmods[$curhook])) {
        $get = array();
        if ($layout == 'bycat')
            $get['orderBy'] = 'category/name';
        // Get list of active modules
        $modules = xarMod::apiFunc('modules', 'admin', 'getlist', $get);
        if (!isset($modules)) return;
        // get list of modules / itemtypes this module is hooked to
        $obssubjects = xarHooks::getObserverSubjects($curhook);
        $cats = array();
        $subjects = array();
        foreach ($modules as $k => $modinfo) {
            $modname = $modinfo['name'];
            $cat = $modinfo['category'];
            if (!isset($cats[$cat]) && $layout == 'bycat') $cats[$cat] = array();

            // check if hooked to all itemtypes
            $hookstate = 0;
            try {
                $itemtypes = xarMod::apiFunc($modname,'user','getitemtypes',array());
            } catch ( FunctionNotFoundException $e) {
                $itemtypes = array();
            }
            if (!empty($obssubjects[$modname][0][0])) {
                // Hooked by ALL scopes to ALL itemtypes
                $hookstate = 1;
            } elseif (!empty($obssubjects[$modname][0])) {
                // Hooked by SOME scopes to ALL itemtypes 
                if (!empty($hookmods[$curhook]['scopes'])) {
                    foreach ($hookmods[$curhook]['scopes'] as $scope => $val) {
                        $ishooked = !empty($obssubjects[$modname][0][$scope]);
                        if ($ishooked) $hookstate = 2;
                        $itemtypes[0]['scopes'][$scope] = $ishooked;
                    }
                }
            } 

            if (!empty($itemtypes)) {
                // Hooked by SOME scopes to SOME itemtypes
                foreach ($itemtypes as $id => $itemtype) {
                    if (empty($id)) continue;
                    $itemtypes[$id]['scopes'] = array();                    
                    if ($hookstate != 0) {
                        $itemtypes[$id]['scopes'][0] = 0;
                        // already matched the state
                        $ishooked = false;
                    } else {
                        if (!empty($obssubjects[$modname][$id][0])) {
                            // ALL scopes this itemtype
                            $itemtypes[$id]['scopes'][0] = 1;
                            $newstate = 3;
                        } else {
                            if (!empty($hookmods[$curhook]['scopes'])) {
                                // SOME scopes this itemtype
                                foreach ($hookmods[$curhook]['scopes'] as $scope => $val) {
                                    $ishooked = !empty($obssubjects[$modname][$id][$scope]);
                                    if ($ishooked) {
                                        $newstate = 3;
                                        $itemtypes[$id]['scopes'][0] = 2;
                                    }
                                    $itemtypes[$id]['scopes'][$scope] = $ishooked;                 
                                }
                            }
                            if (!isset($itemtypes[$id]['scopes'][0]))
                                $itemtypes[$id]['scopes'][0] = 0;
                        }
                        
                    }
                }
                if (!empty($newstate)) { $hookstate = $newstate; unset($newstate); }        
            }

            // add itemtypes to modinfo 
            $modinfo['itemtypes'] = $itemtypes;
            // add hook state
            $modinfo['hookstate'] = $hookstate;
            if ($layout == 'bycat') {
                $cats[$cat][$modname] = $modinfo;       
            } else {
                $subjects[$modname] = $modinfo;
            }
        }
        if ($layout == 'bycat') {
            $data['cats'] = $cats;        
        } else {
            $data['subjects'] = $subjects;
        }
        
    }

    $data['observers'] = $hookmods;
    $data['curhook'] = $curhook;
    $data['authid'] = xarSecGenAuthKey();
    
    if (empty($return_url)) $return_url = null;
    $data['return_url'] = $return_url;
    
    return $data;    
}

?>
