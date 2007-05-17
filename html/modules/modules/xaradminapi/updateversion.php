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
 * Update the module version in the database
 *
 * @author Xaraya Development Team
 * @param $args['regId'] the id number of the module to update
 * @returns bool
 * @return true on success, false on failure
 */
function modules_adminapi_updateversion($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regId)) throw new EmptyParameterException('redId');

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regId")) return;

    //  Get database connection and tables
    $dbconn = xarDB::getConn();
    $xartable =& xarDBGetTables();
    $modules_table = $xartable['modules'];

    // Get module information from the filesystem
    $fileModule = xarModAPIFunc('modules',
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
