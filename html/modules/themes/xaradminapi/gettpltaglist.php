<?php
/**
 * Get registered template tags
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Get registered template tags
 *
 * @author Marty Vance
 * @param none
 * @returns array
 * @return array of themes in the database
 * @Author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_adminapi_gettpltaglist($args)
{
    $dbconn = xarDB::getConn();
    $xartable =& xarDBGetTables();

    extract($args);

    $aTplTags = array();

    // Get all registered tags from the DB
    $bindvars = array();
    $sSql = "SELECT tags.id, tags.name, mods.name
             FROM $xartable[template_tags] tags, $xartable[modules] mods
             WHERE mods.id = tags.module_id ";
    if (isset($module) && trim($module) != '') {
        $sSql .= " AND mods.name = ?";
        $bindvars[] = $module;
    }
    if (isset($id) && trim($id) != '') {
        $sSql .= " AND tags.id = ? ";
        $bindvars[] = $id;
    }
    $stmt = $dbconn->prepareStatement($sSql);
    $oResult = $stmt->executeQuery($bindvars);

    while($oResult->next()) {
            $aTplTags[] = array(
                    'id'      => $oResult->fields[0],
                    'name'    => $oResult->fields[1],
                    'module'  => $oResult->fields[2]
                );
    }
    $oResult->close();

    return $aTplTags;
}

?>
