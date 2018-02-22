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
 * Initialise a module
 *
 * @author Xaraya Development Team
 * @param array    $args array of optional parameters<br/>
 *        string   $args['regid'] registered module id
 *        string   $args['name'] module's name
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, MODULE_NOT_EXIST
 */
function modules_adminapi_initialise(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (isset($name)) $regid = xarMod::getRegid($name, 'module');
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
        die($msg);
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
    // notify any observers that this module was initialised 
    // NOTE: the ModInitialise event observer notifies ModuleInit hooks 
    xarEvents::notify('ModInitialise', $modInfo['name']);
    // Success
    return true;
}
?>