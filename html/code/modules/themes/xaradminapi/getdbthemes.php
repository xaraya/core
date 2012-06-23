<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
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
    $xartable = xarDB::getTables();

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
                                  'state'   => $themeInfo['state'],
                                  'class'   => $themeInfo['class']);
    }
    $result->close();

    return $dbThemes;
}
?>
