<?php

/**
 * Get all modules in the database
 *
 * @param none
 * @returns array
 * @return array of modules in the database
 */
function modules_adminapi_getdbmodules()
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dbModules = array();

    // Get all modules in DB
    $sql = "SELECT $xartable[modules].xar_regid, xar_name, xar_directory, xar_version, xar_mode, xar_state
              FROM $xartable[modules] LEFT JOIN $xartable[module_states] ON $xartable[modules].xar_regid = $xartable[module_states].xar_regid";
    $result = $dbconn->Execute($sql);
    if (!$result) return;

    while(!$result->EOF) {
        list($regid, $name, $directory, $version, $mode, $state) = $result->fields;

        //Push it into array (should we change to index by regid instead?)
        $dbModules[$name] = array('name'    => $name,
                                  'regid'   => $regid,
                                  'version' => $version,
                                  'mode'    => $mode,
                                  'state'   => $state);
        $result->MoveNext();
    }
    $result->Close();

    return $dbModules;
}

?>
