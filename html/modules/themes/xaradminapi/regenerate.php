<?php
/**
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
 * @raise NO_PERMISSION
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
                $msg = xarML('The same registered ID (#(1)) was found belonging to a #(2) theme in the file system and a registered #(3) theme in the database. Please correct this and regenerate the list.', $dbtheme['regid'], $themeinfo['name'], $dbtheme['name']);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
            // Bail if 2 themes have the same name but not the same regid
            if(($themeinfo['name'] == $dbtheme['name']) && ($themeinfo['regid'] != $dbtheme['regid'])) {
                $msg = xarML('The theme #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.', $themeinfo['name'], $themeinfo['regid'], $dbtheme['regid']);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
        }
    }
    //Setup database object for theme insertion
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    // See if we have gained any themes since last generation,
    // or if any current themes have been upgraded
    foreach ($fileThemes as $name => $themeInfo) {

        if (empty($dbThemes[$name])) {
            // New theme
            
            if (empty($themeInfo['xar_version'])){
                $themeInfo['xar_version'] = '1.0.0';
            }

            $themeId = $dbconn->GenId($xartable['themes']);
            $sql = "INSERT INTO $xartable[themes]
                      (xar_id, xar_name, xar_regid, xar_directory,
                       xar_author, xar_homepage, xar_email, xar_description,
                       xar_contactinfo, xar_publishdate, xar_license,
                       xar_version, xar_xaraya_version, xar_bl_version,
                       xar_class)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $bindvars = array($themeId,$themeInfo['name'],$themeInfo['regid'],
                              $themeInfo['directory'],$themeInfo['author'],
                              $themeInfo['homepage'],$themeInfo['email'],
                              $themeInfo['description'],$themeInfo['contact_info'],
                              $themeInfo['publish_date'],$themeInfo['license'],
                              $themeInfo['version'],$themeInfo['xar_version'],
                              $themeInfo['bl_version'],$themeInfo['class']);
            $result = $dbconn->Execute($sql,$bindvars);
            if (!$result) return;

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
                    if (!isset($set)) die('upgrade');
                }
        }
    }
    return true;
}
?>