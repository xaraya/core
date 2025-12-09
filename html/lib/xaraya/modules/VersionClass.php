<?php

/**
 * Module version class
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use xarClassMap;
use sys;

/**
 * Module version class
 */
class VersionClass
{
    public static function getFileInfo(string $modName)
    {
        $modOsDir = strtolower($modName);
        $result = xarClassMap::findVersion($modName);
        if (!empty($result) && class_exists($result['classname'])) {
            $versionCall = new $result['classname']();
            $modversion = $versionCall();
            // set directory here if needed
            $modversion['directory'] ??= $modOsDir;
            return static::parseFileInfo($modversion);
        }
        return static::getLegacyInfo($modOsDir);
    }

    public static function getLegacyInfo($modOsDir)
    {
        // Spliffster, additional mod info from modules/$modOsDir/xarversion.php
        $fileName = sys::code() . 'modules/' . $modOsDir . '/xarversion.php';
        if (!file_exists($fileName)) {
            // throw new FileNotFoundException($fileName);
            return [];
        }
        $modversion = [];
        include $fileName;
        // set directory here if needed
        $modversion['directory'] ??= $modOsDir;
        return static::parseFileInfo($modversion);
    }

    public static function parseFileInfo($version)
    {
        // name and id are required, assert them, otherwise the module is invalid
        assert(isset($version["name"]) && isset($version["id"]));
        $fileInfo = [];
        $fileInfo['name']           = $version['name'];
        $fileInfo['regid']          = (int) $version['id'];
        $fileInfo['displayname']    = $version['displayname'] ?? $version['name'];
        $fileInfo['description']    = $version['description'] ?? false;
        $fileInfo['displaydescription'] = $version['displaydescription'] ?? $fileInfo['description'];
        $fileInfo['admin']          = isset($version['admin']) ? (bool) $version['admin'] : false;
        $fileInfo['admin_capable']  = isset($version['admin']) ? (bool) $version['admin'] : false;
        $fileInfo['user']           = isset($version['user']) ? (bool) $version['user'] : false;
        $fileInfo['user_capable']   = isset($version['user']) ? (bool) $version['user'] : false;
        $fileInfo['securityschema'] = $version['securityschema'] ?? false;
        $fileInfo['class']          = $version['class'] ?? false;
        $fileInfo['category']       = $version['category'] ?? false;
        $fileInfo['locale']         = $version['locale'] ?? 'en_US.iso-8859-1';
        $fileInfo['author']         = $version['author'] ?? false;
        $fileInfo['contact']        = $version['contact'] ?? false;
        $fileInfo['dependency']     = $version['dependency'] ?? [];
        $fileInfo['dependencyinfo'] = $version['dependencyinfo'] ?? [];
        $fileInfo['propertyinfo']   = $version['propertyinfo'] ?? [];
        $fileInfo['extensions']     = $version['extensions'] ?? [];
        $fileInfo['directory']      = $version['directory'] ?? false;
        $fileInfo['homepage']       = $version['homepage'] ?? false;
        $fileInfo['email']          = $version['email'] ?? false;
        $fileInfo['contact_info']   = $version['contact_info'] ?? false;
        $fileInfo['publish_date']   = $version['publish_date'] ?? false;
        $fileInfo['license']        = $version['license'] ?? false;
        $fileInfo['version']        = $version['version'] ?? false;
        // Check that 'xar_version' key exists before assigning
        if (!$fileInfo['version'] && isset($version['xar_version'])) {
            $fileInfo['version'] = $version['xar_version'];
        }
        $fileInfo['bl_version']     = $version['bl_version'] ?? false;
        $fileInfo['namespace']      = $version['namespace'] ?? '';
        $fileInfo['twigtemplates']  = $version['twigtemplates'] ?? false;
        $fileInfo['twigextension']  = $version['twigextension'] ?? '.html.twig';

        return $fileInfo;
    }
}
