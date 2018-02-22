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
 * Activate a module if it has an active function, otherwise just set the state to active
 *
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] module's registered id
 *        string   $args['name'] module's name
 * @return boolean
 * @throws BAD_PARAM
 */
function modules_adminapi_activate(Array $args=array())
{
    extract($args);

    // Argument check
    if (isset($name)) $regid = xarMod::getRegid($name, 'module');
    if (!isset($regid)) throw new EmptyParameterException('regid');

    $modInfo = xarMod::getInfo($regid);

    if($modInfo['state'] == XARMOD_STATE_UNINITIALISED) {
        throw new Exception("Calling activate function while module is uninitialised");
    }
    // Module activate function
    if (!xarMod::apiFunc('modules','admin', 'executeinitfunction',
                           array('regid'    => $regid,
                                 'function' => 'activate'))) {
        $msg = xarML('Unable to execute "activate" function in the xarinit.php file of module (#(1))', $modInfo['displayname']);
        throw new Exception($msg);
    }

    // Update state of module
    $res = xarMod::apiFunc('modules','admin','setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_ACTIVE));

    if (function_exists('xarOutputFlushCached') && function_exists('xarModGetName') && xarModGetName() != 'installer') {
        xarOutputFlushCached('base');
        xarOutputFlushCached('modules');
        xarOutputFlushCached('base-block');
    }
    // notify any observers that this module was activated 
    // NOTE: the ModActivate event observer notifies ModuleActivate hooks 
    xarEvents::notify('ModActivate', $modInfo['name']);
    return true;
}
?>
