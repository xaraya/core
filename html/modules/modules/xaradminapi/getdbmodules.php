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
    $sql = "SELECT xar_regid
              FROM $xartable[modules]";
    $result = $dbconn->Execute($sql);
    if (!$result) return;

    while(!$result->EOF) {
        list($modRegId) = $result->fields;
        //Get Module Info
        $modInfo = xarModGetInfo($modRegId);
        if (!isset($modInfo)) return;

        $name = $modInfo['name'];
        //Push it into array (should we change to index by regid instead?)
        $dbModules[$name] = array('name'    => $name,
                                  'regid'   => $modRegId,
                                  'version' => $modInfo['version'],
                                  'mode'    => $modInfo['mode'],
                                  'state'   => $modInfo['state']);
        $result->MoveNext();
    }
    $result->Close();

    return $dbModules;
}

?>