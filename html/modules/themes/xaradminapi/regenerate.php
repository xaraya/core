<?php
/**
 * Regenerate theme list
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Regenerate theme list
 *
 * @author Marty Vance
 * @param none
 * @returns bool
 * @return true on success, false on failure
 * @throws NO_PERMISSION
 */
function themes_adminapi_regenerate()
{
// Security Check
    if(!xarSecurityCheck('AdminTheme')) return;

    //Finds and updates missing modules
    if (!xarModAPIFunc('themes','admin','checkmissing')) {return;}

    //Get all themes in the filesystem
    $fileThemes = xarModAPIFunc('themes','admin','getfilethemes');
    if (!isset($fileThemes)) return;

    // Get all themes in DB
    $dbThemes = xarModAPIFunc('themes','admin','getdbthemes');
    if (!isset($dbThemes)) return;

    // See if we have lost any themes since last generation
/*     foreach ($dbThemes as $name => $themeInfo) { */
/*         if (empty($fileThemes[$name])) { */
/*             // Old theme */
/*             // Get theme ID */
/*             $regId = $themeInfo['regid']; */
/*             // Set state of theme to 'missing' */
/*             $set = xarModAPIFunc('themes', */
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
            if(($themeinfo['regid'] == $dbtheme['regid']) && ($themeinfo['name'] != $dbtheme['name'])) {
                $msg = 'The same registered ID (#(1)) was found belonging to a #(2) theme in the file system and a registered #(3) theme in the database. Please correct this and regenerate the list.';
                $vars = array($dbtheme['regid'], $themeinfo['name'], $dbtheme['name']);
                throw new DuplicateException($vars,$msg);
            }
            // Bail if 2 themes have the same name but not the same regid
            if(($themeinfo['name'] == $dbtheme['name']) && ($themeinfo['regid'] != $dbtheme['regid'])) {
                $msg = 'The theme #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.';
                $vars = array($themeinfo['name'], $themeinfo['regid'], $dbtheme['regid']);
                throw new DuplicateException($vars, $msg);
            }
        }
    }
    //Setup database object for theme insertion
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    // See if we have gained any themes since last generation,
    // or if any current themes have been upgraded
    foreach ($fileThemes as $name => $themeInfo) {

        if (empty($dbThemes[$name])) {
            // New theme

            $sql = "INSERT INTO $xartable[themes]
                      (name, regid, directory, version)
                    VALUES (?,?,?,?)";
            $bindvars = array($themeInfo['name'],$themeInfo['regid'],
                              $themeInfo['directory'], $themeInfo['version']);
            $result = $dbconn->Execute($sql,$bindvars);

            $set = xarModAPIFunc('themes',
                                'admin',
                                'setstate',
                                array('regid' => $themeInfo['regid'],
                                      'state' => XARTHEME_STATE_UNINITIALISED));
            if (!isset($set)) return;
        } else {
          // BEGIN bugfix (561802) - cmgrote
            if ($dbThemes[$name]['version'] != $themeInfo['version'] && $dbThemes[$name]['state'] != XARTHEME_STATE_UNINITIALISED) {
                    $set = xarModAPIFunc('themes','admin','setstate',
                                        array('regid' => $dbThemes[$name]['regid'], 'state' => XARTHEME_STATE_UPGRADED));
                    assert('isset($set)); /* Setting the state of theme failed */');
                }
        }
    }
    return true;
}
?>
