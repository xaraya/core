<?php

/**
 * Regenerate theme list
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */

/**
 * Regenerate theme list
 *
 * @author Marty Vance
 * @return boolean true on success, false on failure
 * @throws NO_PERMISSION
 */
function themes_adminapi_regenerate()
{
// Security Check
    if (!xarSecurityCheck('AdminThemes'))
        return;

    //Finds and updates missing modules
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance('themes');
    if (!$installer->checkformissing()) {
        return;
    }

    //Get all themes in the filesystem
    $fileThemes = xarMod::apiFunc('themes', 'admin', 'getfilethemes');
    if (!isset($fileThemes))
        return;

    // Get all themes in DB
    $dbThemes = xarMod::apiFunc('themes', 'admin', 'getdbthemes');
    if (!isset($dbThemes))
        return;

    // See if we have lost any themes since last generation
    /*     foreach ($dbThemes as $name => $themeInfo) { */
    /*         if (empty($fileThemes[$name])) { */
    /*             // Old theme */
    /*             // Get theme ID */
    /*             $regId = $themeInfo['regid']; */
    /*             // Set state of theme to 'missing' */
    /*             $set = xarMod::apiFunc('themes', */
    /*                                 'admin', */
    /*                                 'setstate', */
    /*                                 array('regid'=> $regId, */
    /*                                       'state'=> XARTHEME_STATE_MISSING)); */
    /*             //throw back */
    /*             if (!isset($set)) return; */
    /*  */
    /*             unset($dbThemes[$name]); */
    /*         } */
    /*     } */
    // See if we have gained any themes since last generation,
    // or if any current themes have been upgraded
    foreach ($fileThemes as $name => $themeinfo) {
        foreach ($dbThemes as $dbtheme) {
            // Bail if 2 themes have the same regid but not the same name
            if (($themeinfo['regid'] == $dbtheme['regid']) && ($themeinfo['name'] != $dbtheme['name'])) {
                $msg = 'The same registered ID (#(1)) was found belonging to a #(2) theme in the file system and a registered #(3) theme in the database. Please correct this and regenerate the list.';
                $vars = array($dbtheme['regid'], $themeinfo['name'], $dbtheme['name']);
                throw new DuplicateException($vars, $msg);
            }
            // Bail if 2 themes have the same name but not the same regid
            if (($themeinfo['name'] == $dbtheme['name']) && ($themeinfo['regid'] != $dbtheme['regid'])) {
                $msg = 'The theme #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.';
                $vars = array($themeinfo['name'], $themeinfo['regid'], $dbtheme['regid']);
                throw new DuplicateException($vars, $msg);
            }
        }
    }
    //Setup database object for theme insertion
    $dbconn = xarDB::getConn();
    $xartable = & xarDB::getTables();
    // See if we have gained any themes since last generation,
    // or if any current themes have been upgraded
    foreach ($fileThemes as $name => $themeInfo) {

        if (empty($dbThemes[$name])) {
            // New theme
            $sql = "INSERT INTO $xartable[themes]
                      (name, regid, directory, version, class)
                    VALUES (?,?,?,?,?)";
            $bindvars = array($themeInfo['name'], $themeInfo['regid'], $themeInfo['directory'], $themeInfo['version'], $themeInfo['class']);
            $result = $dbconn->Execute($sql, $bindvars);

            $set = xarMod::apiFunc('themes', 'admin', 'setstate', array('regid' => $themeInfo['regid'],
                        'state' => XARTHEME_STATE_UNINITIALISED));
            if (!isset($set))
                return;
        } else {
            // BEGIN bugfix (561802) - cmgrote
            if ($dbThemes[$name]['version'] != $themeInfo['version'] && $dbThemes[$name]['state'] != XARTHEME_STATE_UNINITIALISED) {
                $set = xarMod::apiFunc('themes', 'admin', 'setstate', array('regid' => $dbThemes[$name]['regid'], 'state' => XARTHEME_STATE_UPGRADED));
                assert('isset($set); /* Setting the state of theme failed */');
            }
        }
    }
    // Reinit the theme configurations
    sys::import('modules.themes.class.initialization');
    ThemeInitialization::importConfigurations();

    return true;
}

?>