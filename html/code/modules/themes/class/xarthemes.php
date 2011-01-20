<?php
/**
 * Xaraya Themes class library
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
**/

/**
 * Base Themes class
**/
class xarThemes extends Object
{
    const COMMON = 'common'; // themes/common

/**
 * Find file function
 *
 * Returns the full URL or relative path from webroot to a file
 * obeying standard template cascade paths
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param  string  $scope the scope in which to look for files, required
 * @param  string  $filename the name of the file to look for, required
 * @param  string  $base optional sub folder to look in
 * @param  string  $package the name of the theme, module or property to look in<br/>
 *                 Optional in theme scope, default ""<br/>
 *                 Optional in module scope, default current module<br/>
 *                 Optional in block scope, default current block module<br/>
 *                 Required in property scope
 * @param  boolean $rel optionally return relative path from web root, default false
 * @return string path to file if found, empty otherwise
 * @throws none
**/
    public function findFile($scope, $filename, $base='', $package='', $rel=false)
    {
        if (empty($scope) || empty($filename)) return;
        
        // set common template paths
        $themesDir = xarConfigVars::get(null, 'Site.BL.ThemesDirectory', 'themes');
        $themeDir = xarTplGetThemeDir();
        $codeDir = sys::code();
        $baseUrl = xarServer::getBaseURL();
        $commonDir = is_dir($themesDir.'/'.xarThemes::COMMON) ? $themesDir.'/'.xarThemes::COMMON : 'themes/'.xarThemes::COMMON;

        // set path part relative to common cascade paths
        $relpath = !empty($base) ? $base . '/' . $filename : $filename;
        
        $paths = array();
        switch ($scope) {
            case 'common':
            case 'theme':
                if (!empty($package))
                    $paths[] = $themesDir . '/' . $package . '/' . $relpath;
                $paths[] = $themeDir . '/' . $relpath;
                $paths[] = $commonDir . '/' . $relpath;
            break;
            case 'module':
                if (empty($package))
                    $package = xarMod::getName();
            case 'block':
                if (empty($package))
                    $package = xarVarGetCached('Security.Variables', 'currentmodule');
                $modInfo = xarMod::getBaseInfo($package);
                if (!isset($modInfo)) return;
                $package = $modInfo['osdirectory'];
                $paths[] = $themeDir . '/modules/' . $package . '/' . $relpath;
                $paths[] = $commonDir . '/modules/' . $package . '/' . $relpath;
                $paths[] = $codeDir . 'modules/' . $package . '/xartemplates/' . $relpath;
                break; 
            case 'property':
                if (empty($package)) return;
                $paths[] = $themeDir . '/properties/' . $package . '/' . $relpath;
                $paths[] = $commonDir . '/properties/' . $package . '/' . $relpath;
                $paths[] = $codeDir . 'properties/' . $package . '/xartemplates/' . $relpath;
                break;
        }
        
        if (!empty($paths)) {
            foreach ($paths as $path) {
                if (!file_exists($path)) continue;
                $filepath = $path;
                break;
            }
        }
        if (empty($filepath)) return;
        
        // return relative path
        if ($rel) 
            return $filepath;
        
        // return full url
        return $baseUrl.$filepath;
    }        
}
?>