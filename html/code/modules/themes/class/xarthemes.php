<?php
class xarThemes extends Object
{
    const COMMON = 'common'; // themes/common

    public function findFile($scope, $filename, $base='', $package='', $rel=false)
    {
        if (empty($scope) || empty($filename)) return;
        
        // set common template paths
        $themesDir = xarConfigVars::get(null, 'Site.BL.ThemesDirectory', 'themes');
        $themeDir = xarTplGetThemeDir();
        $codeDir = sys::code();
        $baseUrl = xarServer::getBaseURL();
        $commonDir = is_dir($themesDir.'/'.xarThemes::COMMON) ? $themesDir.'/'.xarThemes::COMMON : 'themes/'.xarThemes::COMMON;

        // set path part relative to common paths
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