<?php
/**
 * Get all modules in the database
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Get all modules in the database
 *
 * @author Xaraya Development Team
 * @param $args['regid'] - optional regid to retrieve
 * @returns array
 * @return array of modules in the database
 */
function modules_adminapi_getdbmodules($args)
{
    // Get arguments
    extract($args);

    // Check for $regId
    $modregid = 0;
    if (isset($regId)) {
        $modregid = $regId;
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $dbModules = array();

    // Get all modules in DB
    $sql = "SELECT regid, name, directory, class, version, mode, state
            FROM $xartable[modules] ";

    if ($modregid) {
        $sql .= " WHERE $xartable[modules].regid = ?";
    }
    $stmt = $dbconn->prepareStatement($sql);
    $result = $stmt->executeQuery(array($modregid));

    while($result->next()) {
        list($regid, $name, $directory, $class, $version, $mode, $state) = $result->fields;

        // If returning one module, then push array without name index
        if ($modregid) {
            $dbModules = array('name'    => $name,
                               'regid'   => $regid,
                               'version' => $version,
                               'class'   => $class,
                               'mode'    => $mode,
                               'state'   => $state);
        } else {
            //Push it into array (should we change to index by regid instead?)
            $dbModules[$name] = array('name'    => $name,
                                      'regid'   => $regid,
                                      'version' => $version,
                                      'class'   => $class,
                                      'mode'    => $mode,
                                      'state'   => $state);
        }
    }
    $result->close();

    return $dbModules;
}

?>
