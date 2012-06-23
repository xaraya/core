<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */

/**
 * Obtain list of hooks (optionally for a particular module)
 *
 * @param array    $args array of optional parameters<br/>
 *        string   $args['modName'] optional module we're looking for
 * @return array of known hooks
 */
function modules_adminapi_gethooklist(Array $args=array())
{
    // Security Check
    // @CHECKME: is this info not useful to other modules?
    if(!xarSecurityCheck('ManageModules')) return;

    // Get arguments from argument array
    extract($args);

    // get a list of observer (hook) modules 
    $hookmods = xarHooks::getObserverModules();


    // reconstruct hooklist[hookmod][object:action:area][hookedto][itemtype] for anyone still using this 
    $hooklist = array();
    foreach ($hookmods as $modname => $info) {
        // pointless sanity check
        if (!isset($hooklist[$modname]))
            $hooklist[$modname] = array();
        // get list of modules / itemtypes this module is hooked to
        $hookedto = xarHooks::getObserverSubjects($modname);
        if (!empty($info['hooks'])) {
            
            foreach ($info['hooks'] as $event => $hook) {                
                if (!empty($hook['scope'])) {
                    $object = $hook['scope'];
                } else {
                    $replace = array('modifyconfig', 'updateconfig', 'create', 'delete', 'modify', 'update', 'remove', 'search', 'display', 'waitingcontent', 'init','activate', 'upgrade', 'view', 'submit');
                    $object = str_replace($replace, '', strtolower($event));
                } 
                $action = str_replace(strtolower($object), '', strtolower($event));
                $area = strtolower($hook['area']);
                if (!isset($hooklist[$modname]["$object:$action:$area"]))
                    $hooklist[$modname]["$object:$action:$area"] = array();
                if (!empty($hookedto)) {
                    foreach ($hookedto as $subject => $itemtypes) {
                        foreach ($itemtypes as $itemtype => $scopes) {
                            if (!empty($scopes[0]) || !empty($scopes[$scope])) {
                                 $hooklist[$modname]["$object:$action:$area"][$subject][$itemtype] = 1;
                            }
                        }
                    }
                }
            }                        
        }       
    }
    return $hooklist;
}
?>