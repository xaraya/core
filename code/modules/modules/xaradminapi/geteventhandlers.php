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
 * Get the list of active event handlers
 *
 * @author Xaraya Development Team
 * @param none
 * @return bool null on exceptions, true on sucess to update
 * @throws NO_PERMISSION
 */
function modules_adminapi_geteventhandlers()
{
    static $check = false;

    //Now with dependency checking, this function may be called multiple times
    //Let's check if it already return ok and stop the processing here
    if ($check) {return true;}

    $modlist = xarModAPIFunc('modules','admin','getlist',
                             array('filter' => array('State' => XARMOD_STATE_ACTIVE)));

    $todo = array();
    // @todo: this looks familiar, xarEvt.php has the same?
    foreach ($modlist as $mod) {
        $modName = $mod['name'];
        $modDir = $mod['osdirectory'];
        // use the directory here, not the name
        $xarapifile = "modules.{$modDir}.xareventapi";
        // try to include the event API for this module
        try {
            // @todo does this need to be wrapped for multiple inclusion?
            sys::import($xarapifile);
            $modName = strtolower($modName);
            $todo[$modName] = $modDir;
        } catch(PHPException $e) {
            // No harm done, well
        }
    }

    $handlers = array();
    if (count($todo) > 0) {
        // get the list of all defined functions
        $functions = get_defined_functions();
        // get the list of all relevant modules
        $filter = join('|', array_keys($todo));
        // see if we have some <module>_eventapi_on<eventname> functions
        foreach ($functions['user'] as $userfunc) {
            if (preg_match("/^($filter)_eventapi_on(.+)$/i", $userfunc, $matches)) {
                $modname = $matches[1];
                $eventname = $matches[2];
                if (!empty($todo[$modname])) {
                    if (!isset($handlers[$eventname])) {
                        $handlers[$eventname] = array();
                    }
                    // save the module directory here too
                    $handlers[$eventname][$modname] = $todo[$modname];
                } else {
                    // ignore event handlers from unknown/inactive modules
                }
            }
        }
    }
    // this gets serialized internally
    xarConfigVars::set(null, 'Site.Evt.Handlers',$handlers);

    $check = true;

    return true;
}

?>
