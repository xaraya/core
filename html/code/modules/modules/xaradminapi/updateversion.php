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
 * Update the module version in the database
 *
 * @author Xaraya Development Team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regId'] the id number of the module to update
 * @return boolean true on success, false on failure
 */
function modules_adminapi_updateversion(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regId)) throw new EmptyParameterException('redId');

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regId")) return;

    //  Get database connection and tables
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $modules_table = $xartable['modules'];

    // Get module information from the filesystem
    $fileModule = xarMod::apiFunc('modules',
                                'admin',
                                'getfilemodules',
                                array('regId' => $regId));
    if (!isset($fileModule)) return;

    // Update database version
    $sql = "UPDATE $modules_table SET version = ? WHERE regid = ?";
    $bindvars = array($fileModule['version'],$fileModule['regid']);

    $result =& $dbconn->Execute($sql,$bindvars);

    return true;
}

?>
