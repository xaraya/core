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
 * Upgrade a module
 *
 * @author Xaraya Development Team
 * @param array   $args array of parameters
 * @param regid registered module id
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM
 */
function modules_adminapi_upgrade(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Get module information
    $modInfo = xarMod::getInfo($regid);
    if (empty($modInfo)) {
        xarSession::setVar('errormsg', xarML('No such module'));
        return false;
    }

    // Module deletion function
    if (!xarMod::apiFunc('modules',
                       'admin',
                       'executeinitfunction',
                       array('regid'    => $regid,
                             'function' => 'upgrade'))) {
        //Raise an Exception
        return;
    }

    // Update state of module
    $res = xarMod::apiFunc('modules',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_INACTIVE));
    if (!isset($res)) return;

    // Get the new version information...
    $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
    if (!isset($modFileInfo)) return;

    // Bug 1671 - Invalid SQL
    // If the module fields returned from xarMod_getFileInfo()
    // are set to false, then they must be set to a some valid value
    // or a SQL error will occur due to null and zero length fields.
    if (!$modFileInfo['admin_capable'])
        $modFileInfo['admin_capable'] = 0;
    if (!$modFileInfo['user_capable'])
        $modFileInfo['user_capable'] = 0;
    if (!$modFileInfo['class'])
        $modFileInfo['class'] = 'Miscellaneous';
    if (!$modFileInfo['category'])
        $modFileInfo['category'] = 'Miscellaneous';

    // Note the changes in the database...
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $sql = "UPDATE $xartable[modules]
            SET version = ?, admin_capable = ?, user_capable = ?,
                class = ?, category = ?
            WHERE regid = ?";
    $bindvars = array($modFileInfo['version'], $modFileInfo['admin_capable'],
                      $modFileInfo['user_capable'],$modFileInfo['class'],
                      $modFileInfo['category'], $regid);
    $dbconn->Execute($sql,$bindvars);

    // Message to display in the module list view (only for core modules atm)
    if(!xarSession::getVar('statusmsg')){
        if(substr($modFileInfo['class'], 0, 4)  == 'Core'){
            xarSession::setVar('statusmsg', $modInfo['name']);
        }
    } else {
        if(substr($modFileInfo['class'], 0, 4)  == 'Core'){
            xarSession::setVar('statusmsg', xarSession::getVar('statusmsg') . ', '. $modInfo['name']);
        }
    }
    // Success
    return true;
}

?>
