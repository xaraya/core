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
 * Configure hooks by hook module
 *
 * @author Xaraya Development Team
 * @param $args['curhook'] current hook module (optional)
 * @param $args['return_url'] URL to return to after updating the hooks (optional)
 *
 */
function modules_admin_hooks($args)
{
// Security Check
    if(!xarSecurityCheck('ManageModules')) return;

    if (!xarVarFetch('hook', 'isset', $curhook, null, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('layout', 'pre:trim:lower:enum:bycat', $layout, 'bycat', XARVAR_NOT_REQUIRED)) return;
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
            $hookstate = !empty($obssubjects[$modname][0]);
            try {
                $itemtypes = xarMod::apiFunc($modname,'user','getitemtypes',array());
            } catch ( FunctionNotFoundException $e) {
                $itemtypes = array();
            }
            if (!empty($itemtypes)) {
                foreach ($itemtypes as $id => $itemtype) {
                    if ($hookstate == 1) {
                        // hooked to all itemtypes
                        $ishooked = false;
                    } else {
                        // otherwise see if hooked
                        $ishooked = !empty($obssubjects[$modname][$id]);
                    }
                    // set hook state to some if not hooked to all                    
                    if ($hookstate != 1 && $ishooked) 
                        $hookstate = 2;
                    // add ishooked value to itemtype                     
                    $itemtypes[$id]['ishooked'] = $ishooked;
                }
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