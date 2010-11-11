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
 * Initialise a module
 *
 * @author Xaraya Development Team
 * @param array   $args array of parameters
 * @param regid registered module id
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, MODULE_NOT_EXIST
 */
function modules_adminapi_initialise(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Get module information
    $modInfo = xarMod::getInfo($regid);
    if (!isset($modInfo)) throw new ModuleNotFoundException($regid,'Module (regid: $regid) does not exist.');

    //Checks module dependency
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    
    if (!$installer->verifydependency($regid)) {
        //TODO: Add description of the dependencies
        $msg = xarML('The dependencies to initialise the module "#(1)" were not met.', $modInfo['displayname']);
        throw new Exception($msg);
    }

    // Module deletion function
    if (!xarMod::apiFunc('modules',
                       'admin',
                       'executeinitfunction',
                       array('regid'    => $regid,
                             'function' => 'init'))) {
        //Raise an Exception
        return;
    }

    // Update state of module
    $set = xarMod::apiFunc('modules',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_INACTIVE));

    // debug($set);
    if (!isset($set)) {
        $msg = xarML('Module state change failed');
        throw new Exception($msg);
    }

    // Success
    return true;
}
?>