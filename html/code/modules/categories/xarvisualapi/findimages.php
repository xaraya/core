<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * Get a list of images from the modules/categories/xarimages directory
 * (may be overridden by versions in themes/<theme>/modules/categories/images)
 */
function categories_visualapi_findimages()
{
    // theme *overrides* are possible, but the original must reside here
    $basedir = sys::code() . 'modules/categories/xarimages';
    $basedir = realpath($basedir);

    $filetype = '(png|gif|jpg|jpeg)';
    $filelist = array();

    $todo = array();
    array_push($todo, $basedir);
    while (count($todo) > 0) {
        $curdir = array_shift($todo);
        if ($dir = @opendir($curdir)) {
            while(($file = @readdir($dir)) !== false) {
                $curfile = $curdir . '/' . $file;
                if (preg_match("/\.$filetype$/",$file) && is_file($curfile) && filesize($curfile) > 0) {
                    $curfile = preg_replace('#'.preg_quote($basedir,'#').'/#','',$curfile);
                    $filelist[] = $curfile;
                } elseif ($file != '.' && $file != '..' && is_dir($curfile)) {
                    array_push($todo, $curfile);
                }
            }
            closedir($dir);
        }
    }
    natsort($filelist);
    return $filelist;
}

?>