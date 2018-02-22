<?php
/**
 * Deactivate a module 
 *
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * Deactivate a module if it has a deactive function, otherwise just set the state to deactive
 *
 * @author Xaraya Development Team
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['regid'] module's registered id
 *        string   $args['name'] module's name
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM
 */
function modules_adminapi_deactivate(Array $args=array())
{
    extract($args);

    // Argument check
    if (isset($name)) $regid = xarMod::getRegid($name, 'module');
    if (!isset($regid)) throw new EmptyParameterException('regid');

    $modInfo = xarMod::getInfo($regid);

    //Shouldnt we check first if the module is alredy ACTIVATED????
    //What should we do with UPGRADED STATE? What is it meant to?
    //  if ($modInfo['state'] != XARMOD_STATE_ACTIVE)

    // Module activate function
    // only run if the module is actually there. It may have been removed
    if ($modInfo['state'] != XARMOD_STATE_MISSING_FROM_ACTIVE) {
        if (!xarMod::apiFunc('modules','admin','executeinitfunction',
                           array('regid'    => $regid,
                                 'function' => 'deactivate'))) {
            //Raise an Exception
            return;
        }
    }
    // Update state of module
    $res = xarMod::apiFunc('modules','admin','setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_INACTIVE));



    if (function_exists('xarOutputFlushCached')) {
        xarOutputFlushCached('base');
        xarOutputFlushCached('modules');
        xarOutputFlushCached('base-block');
    }
    // notify any observers that this module was deactivated 
    // NOTE: the ModDeactivate event observer notifies ModuleDeactivate hooks 
    xarEvents::notify('ModDeactivate', $modInfo['name']);
    return true;
}
?>
