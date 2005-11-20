<?php
/**
 * Get all modules in the database
 *
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
    $dbconn =& xarDBGetConn();
    // Get arguments
    extract($args);

    // Check for $regId
    $modregid = 0;
    if (isset($regId)) {
        $modregid = $regId;
    }

    $xartable =& xarDBGetTables();

    $dbModules = array();

    // Get all modules in DB
    $sql = "SELECT $xartable[modules].xar_regid, xar_name, xar_directory, xar_class, xar_version, xar_mode, xar_state
              FROM $xartable[modules] LEFT JOIN $xartable[module_states] ON $xartable[modules].xar_regid = $xartable[module_states].xar_regid";

    if ($modregid) {
        $sql .= " WHERE $xartable[modules].xar_regid = $modregid";
    }

    $result = $dbconn->Execute($sql);
    if (!$result) return;

    while(!$result->EOF) {
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
        $result->MoveNext();
    }
    $result->Close();

    return $dbModules;
}

?>
