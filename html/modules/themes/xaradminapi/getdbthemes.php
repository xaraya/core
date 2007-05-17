<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 */

/**
 * Get all themes in the database
 *
 * @author Marty Vance
 * @return array of themes in the database
 */
function themes_adminapi_getdbthemes()
{
    $dbconn = xarDB::getConn();
    $xartable =& xarDBGetTables();

    $dbThemes = array();

    // Get all themes in DB
    $sql = "SELECT regid  FROM $xartable[themes]";
    $result = $dbconn->executeQuery($sql);

    while($result->next()) {
        list($themeRegId) = $result->fields;
        //Get Theme Info
        $themeInfo = xarThemeGetInfo($themeRegId);
        if (!isset($themeInfo)) return;

        $name = $themeInfo['name'];
        //Push it into array (should we change to index by regid instead?)
        $dbThemes[$name] = array('name'    => $name,
                                  'regid'   => $themeRegId,
                                  'version' => $themeInfo['version'],
                                  'state'   => $themeInfo['state']);
    }
    $result->close();

    return $dbThemes;
}
?>
