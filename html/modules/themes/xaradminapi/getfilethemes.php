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
 * Get themes from filesystem
 *
 * @author Marty Vance
 * @param none
 * @returns array
 * @return an array of themes from the file system
 */
function themes_adminapi_getfilethemes()
{
    $fileThemes = array();
    //jojodee, removed hard coded theme path

    //$dh = opendir('themes');
    $dh = opendir(xarConfigGetVar('Site.BL.ThemesDirectory'));
    while ($themeOsDir = readdir($dh)) {
        switch ($themeOsDir) {
            case '.':
            case '..':
            case 'CVS':
            case 'SCCS':
            case 'PENDING':
                break;
            default:
                //jojodee, remove hard coded theme path
                if (is_dir(xarConfigGetVar('Site.BL.ThemesDirectory')."/$themeOsDir")) {

                    // no xartheme.php, no theme
                    $themeFileInfo = xarTheme_getFileInfo($themeOsDir);
                    if (!isset($themeFileInfo)) {
                        continue;
                    }

                    // Found a directory
                    $name         = $themeFileInfo['name'];
                    $regId        = $themeFileInfo['id'];
                    $directory    = $themeFileInfo['directory'];
                    $author       = $themeFileInfo['author'];
                    $homepage     = $themeFileInfo['homepage'];
                    $email        = $themeFileInfo['email'];
                    $description  = $themeFileInfo['description'];
                    $contact_info = $themeFileInfo['contact_info'];
                    $publish_date = $themeFileInfo['publish_date'];
                    $license      = $themeFileInfo['license'];
                    $version      = $themeFileInfo['version'];
                    $xar_version  = isset($themeFileInfo['xar_version']);
                    $bl_version   = $themeFileInfo['bl_version'];
                    $class        = $themeFileInfo['class'];

                    // TODO: beautify :-)
                    if (!isset($regId)) {
                        xarSession::setVar('errormsg', "Theme '$name' doesn't seem to have a registered theme ID defined in xarversion.php - skipping...\nPlease register your theme at http://www.xaraya.com/index.php?module=release&func=addid if you haven't done so yet, and add \$themeversion['id'] = 'your ID'; in xarversion.php");
                        continue;
                    }

                    // TODO: beautify :-)
                    if (!isset($regId) || xarVarPrepForOS($directory) != $themeOsDir) {
                        xarSession::setVar('errormsg', "Theme '$name' exists in ".xarConfigGetVar('Site.BL.ThemesDirectory')."/$themeOsDir but should be in "
                        .xarConfigGetVar('Site.BL.ThemesDirectory').
                        "/$directory according to themes/$themeOsDir/xartheme.php... Skipping this theme until resolved.");
                        continue;
                    }
                    //Defaults
                    if (!isset($version)) {
                        $version = 1.0;
                    }

                    if (!isset($xar_version)) {
                        $xar_version = 1.0;
                    }

                    if (!isset($bl_version)) {
                        $bl_version = 1.0;
                    }

                    //FIXME: <johnny> add class and category checking
                    if (!isset($class)) {
                        $class = '0';
                    }

                    $fileThemes[$name] = array('name'             => $name,
                                               'regid'            => $regId,
                                               'directory'        => $directory,
                                               'author'           => $author,
                                               'homepage'         => $homepage,
                                               'email'            => $email,
                                               'description'      => $description,
                                               'contact_info'     => $contact_info,
                                               'publish_date'     => $publish_date,
                                               'license'          => $license,
                                               'version'          => $version,
                                               'xar_version'      => $xar_version,
                                               'bl_version'       => $bl_version,
                                               'class'            => $class);
                } // if
        } // switch
    } // while
    closedir($dh);
    return $fileThemes;
}

?>
