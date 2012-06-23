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
 * Get themes from filesystem
 *
 * @author Marty Vance
 * @return array the themes from the file system
 */
function themes_adminapi_getfilethemes()
{
    $fileThemes = array();
    $basedir = xarConfigVars::get(null,'Site.BL.ThemesDirectory');

    $dh = opendir($basedir);
    while ($themeOsDir = readdir($dh)) {
        switch ($themeOsDir) {
            case '.':
            case '..':
            case 'CVS':
            case 'SCCS':
            case 'PENDING':
                break;
            default:
                if (is_dir($basedir ."/" . $themeOsDir)) {

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
                        xarSession::setVar('errormsg', 
                          "Theme '$name' exists in $basedir/$themeOsDir " .
                          "but should be in $basedir/$directory according to $basedir/$themeOsDir/xartheme.php... Skipping this theme until resolved.");
                        continue;
                    }
                    //Defaults
                    if (!isset($version)) {
                        $version = 1.0;
                    }

                    if (!isset($xar_version)) {
                        $xar_version = 2.0;
                    }

                    if (!isset($bl_version)) {
                        $bl_version = 2.0;
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
