<?php

/**
 * Get themes from filesystem
 *
 * @param none
 * @returns array
 * @return an array of themes from the file system
 */
function themes_adminapi_getfilethemes()
{
    $fileThemes = array();
    $dh = opendir('themes');

    //SCCS is Bitkeeper's special directory , should we add cvs back into the mix too?
    while ($themeOsDir = readdir($dh)) {
        if ((is_dir("themes/$themeOsDir")) &&
                ($themeOsDir != '.') &&
                ($themeOsDir != '..') &&
                ($themeOsDir != 'SCCS')) {

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
            $xar_version  = $themeFileInfo['xar_version'];
            $bl_version   = $themeFileInfo['bl_version'];
            $class        = $themeFileInfo['class'];

            // TODO: beautify :-)
            if (!isset($regId)) {
                xarSessionSetVar('errormsg', "Theme '$name' doesn't seem to have a registered theme ID defined in xarversion.php - skipping...\nPlease register your theme at http://www.xaraya.com/index.php?module=release&func=addid if you haven't done so yet, and add \$themeversion['id'] = 'your ID'; in xarversion.php");
                continue;
            }

            // TODO: beautify :-)
            if (!isset($regId) || xarVarPrepForOS($directory) != $themeOsDir) {
                xarSessionSetVar('errormsg', "Theme '$name' exists in themes/$themeOsDir but should be in themes/$directory according to themes/$themeOsDir/xartheme.php... Skipping this theme until resolved.");
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
                                        'regid'           => $regId,
                                        'directory'       => $directory,
                                        'author'          => $author,
                                        'homepage'        => $homepage,
                                        'email'           => $email,
                                        'description'     => $description,
                                        'contact_info'    => $contact_info,
                                        'publish_date'    => $publish_date,
                                        'license'         => $license,
                                        'version'         => $version,
                                        'xar_version'     => $xar_version,
                                        'bl_version'      => $bl_version,
                                        'class'           => $class);
        }
    }
    closedir($dh);
    return $fileThemes;
}

?>