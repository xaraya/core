<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminApi;
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi getfilethemes function
 * @extends MethodClass<AdminApi>
 */
class GetfilethemesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get themes from filesystem
     * @author Marty Vance
     * @return array the themes from the file system
     * @see AdminApi::getfilethemes()
     */
    public function __invoke(array $args = [])
    {
        $fileThemes = [];
        $basedir = $this->config()->getVar('Site.BL.ThemesDirectory');

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
                    if (is_dir($basedir . "/" . $themeOsDir)) {
                        // no xartheme.php, no theme
                        $themeFileInfo = xarTheme::getFileInfo($themeOsDir);
                        if (empty($themeFileInfo)) {
                            continue 2;
                        }

                        // Found a directory
                        $name         = $themeFileInfo['name'];
                        $regId        = $themeFileInfo['regid'];
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
                        $twigtemplates = $themeFileInfo['twigtemplates'] ?? false;
                        $twigextension = $themeFileInfo['twigextension'] ?? '.html.twig';

                        // TODO: beautify :-)
                        if (!isset($regId)) {
                            $this->session()->setVar('errormsg', "Theme '$name' doesn't seem to have a registered theme ID defined in xartheme.php - skipping...");
                            continue 2;
                        }

                        // TODO: beautify :-)
                        if (!isset($regId) || $this->var()->prepPath($directory) != $themeOsDir) {
                            $this->session()->setVar(
                                'errormsg',
                                "Theme '$name' exists in $basedir/$themeOsDir "
                              . "but should be in $basedir/$directory according to $basedir/$themeOsDir/xartheme.php... Skipping this theme until resolved."
                            );
                            continue 2;
                        }
                        //Defaults - @todo do we still need this anywhere?
                        if (!isset($xar_version)) {
                            $xar_version = 2.0;
                        }

                        $fileThemes[$name] = ['name'             => $name,
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
                            'class'            => $class,
                            'twigtemplates'    => $twigtemplates,
                            'twigextension'    => $twigextension];
                    } // if
            } // switch
        } // while
        closedir($dh);
        return $fileThemes;
    }
}
