<?php

/**
 * Get registered template tags
 *
 * @param none
 * @returns array
 * @return array of themes in the database
 * @Author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_adminapi_gettpltaglist($args)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
		
		extract($args);

    $aTplTags = array();

    // Get all registered tags from the DB
    $sSql = "SELECT xar_id, xar_name, xar_module
              FROM $xartable[template_tags] WHERE 1=1 ";
		if (isset($module) && trim($module) != '') {
		    $sSql .= " AND xar_module = '$module' ";
		}
		if (isset($id) && trim($id) != '') {
		    $sSql .= " AND xar_id = '$id' ";
		}
		
    $oResult = $dbconn->Execute($sSql);
    if (!$oResult) return;
    if (!$oResult) {
        $sMsg = 'Could not get any Tags';
        xarSessionSetVar('errormsg',xarML($sMsg));
        return false;
    }

    while(!$oResult->EOF) {
		    $aTplTags[] = array(
				    'id'      => $oResult->fields[0], 
				    'name'    => $oResult->fields[1], 
				    'module'  => $oResult->fields[2]
				);
		
        $oResult->MoveNext();
    }
    $oResult->Close();

    return $aTplTags;
}

?>