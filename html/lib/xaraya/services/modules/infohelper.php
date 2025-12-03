<?php

/**
 * Modules Service Helper for Module Information
 * Note: also used by xarTheme::* static methods
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

use Xaraya\Services\ServiceClass;
use xarClassMap;
use ixarMod;
use sys;
use Exception;
use BadParameterException;
use EmptyParameterException;
use IDNotFoundException;

/**
 * Modules Service Helper for Module Information
 * Note: also used by xarTheme::* static methods
 */
class InfoHelper extends ServiceClass
{
    public const SLICE = 'modules.info';

    public $noCacheMod = false;
    public $noCacheTheme = false;
    protected $loadedDbInfoCache = [];
    protected $modAvailableCache = [];

    /**
     * @todo align with xar::mod($modName)->getName() - move back to ModuleService?
     */
    public function getName(?int $regID = null): string
    {
        if (!isset($regID)) {
            $xar = $this->getParent();
            $modName = $xar->req()->getModule();
        } else {
            $modinfo = $this->getInfo($regID);
            $modName = $modinfo['name'];
        }
        assert(!empty($modName));
        return $modName;
    }

    public function getID(string $modName): ?int
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        $ids = $this->getIds($modName);
        if (!isset($ids) || !isset($ids['systemid'])) {
            return null;
        }
        return (int) $ids['systemid'];
    }

    public function getRegID(string $modName, $type = 'module'): int
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        //$ids = $this->getIds($modName, $type);
        //return (isset($ids['regid']) && !is_null($ids['regid'])) ? (int) $ids['regid'] : null;
        // avoid getting module id from $this->getRegID() here
        $fileInfo = $this->getFileInfo($modName, $type);
        return (int) ($fileInfo['regid'] ?? 0);
    }

    public function getDisplayName(string $modName, $type = 'module'): string
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        $modInfo = $this->getFileInfo($modName, $type);
        if (empty($modInfo['displayname'])) {
            $modInfo['displayname'] = $modName;
        }
        $xar = $this->getParent();
        return $xar->mls()->translate($modInfo['displayname']);
    }

    public function getDisplayDescription(string $modName, $type = 'module'): string
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        $modInfo = $this->getFileInfo($modName, $type);
        if (empty($modInfo['displaydescription'])) {
            $modInfo['displaydescription'] = $modName;
        }
        $xar = $this->getParent();
        return $xar->mls()->translate($modInfo['displaydescription']);
    }

    /** @return array<string, mixed> */
    public function getFileInfo(string $modOsDir, $type = 'module'): array
    {
        if (empty($modOsDir)) {
            throw new EmptyParameterException('modOsDir');
        }

        $xar = $this->getParent();
        if (empty($this->noCacheMod) && $xar->mem()->has('Mod.getFileInfos', $modOsDir . " / " . $type)) {
            return $xar->mem()->get('Mod.getFileInfos', $modOsDir . " / " . $type);
        }
        // Log it when it didnt came from cache
        $xar->log()->debug("xar::mod()->getFileInfo: Getting file info of '" . $modOsDir . "' (a " . $type . ")");


        // TODO redo legacy support via type.
        switch ($type) {
            case 'module':
                $result = xarClassMap::findVersion($modOsDir);
                if (!empty($result) && class_exists($result['classname'])) {
                    $versionCall = new $result['classname']();
                    $modversion = $versionCall();
                    // set directory here if needed
                    $modversion['directory'] ??= $modOsDir;
                    // If the locale is already present, it means we can make the translations available
                    if (!empty($xar->mls()->getCurrentLocale())) {
                        $xar->mls()->loadModuleTranslations($modOsDir, '', 'version');
                    }
                    return $this->parseFileInfo($modversion, $modOsDir . " / " . $type);
                }
                // Spliffster, additional mod info from modules/$modOsDir/xarversion.php
                $fileName = sys::code() . 'modules/' . $modOsDir . '/xarversion.php';
                $part = 'xarversion';
                // If the locale is already present, it means we can make the translations available
                if (!empty($xar->mls()->getCurrentLocale())) {
                    $xar->mls()->loadModuleTranslations($modOsDir, '', 'version');
                }
                break;
            case 'theme':
                $fileName = $xar->config()->getVar('Site.BL.ThemesDirectory') . '/' . $modOsDir . '/xartheme.php';
                $part = 'xartheme';
                break;
            default:
                throw new BadParameterException('module/theme type');
        }

        if (!file_exists($fileName)) {
            // Don't raise an exception, it is too harsh, but log it tho (bug 295)
            $xar->log()->warning("xar::mod()->getFileInfo: Could not find xarversion.php, skipping $modOsDir");
            // throw new FileNotFoundException($fileName);
            return [];
        }
        // We can NOT use sys::import here, since the xarversion/xartheme files contain variables only
        // If they were loaded earlier, sys::import does nothing (as it should)
        // since inclusion of variables can be done multiple times (they just get overwritten)
        // the include is safe. Ergo: leave this in place.
        include $fileName;

        if (!isset($themeinfo)) {
            $themeinfo = [];
        }
        if (!isset($modversion)) {
            $modversion = [];
        }

        $version = array_merge($themeinfo, $modversion);
        // set directory here if needed
        $version['directory'] ??= $modOsDir;

        return $this->parseFileInfo($version, $modOsDir . " / " . $type);
    }

    /** @return array<string, mixed> */
    public function getBaseInfo(string $modName, $type = 'module'): array
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        if ($type != 'module' && $type != 'theme') {
            throw new BadParameterException($type, 'The value of the "type" parameter must be "module" or "theme", it was "#(1)"');
        }

        // The $this->noCacheMod flag tells Xaraya *not*
        // to cache module (+state) where this would lead to problems
        // like in the installer for example.
        if ($type == 'module') {
            $cacheCollection = 'Mod.BaseInfos';
            $checkNoState = $this->noCacheMod;
        } else {
            $cacheCollection = 'Theme.BaseInfos';
            $checkNoState = $this->noCacheTheme;
        }

        $xar = $this->getParent();
        if (empty($checkNoState) && $xar->mem()->has($cacheCollection, $modName)) {
            return $xar->mem()->get($cacheCollection, $modName);
        }
        // Log it when it doesnt come from the cache
        $xar->log()->debug("xar::mod()->getBaseInfo: Getting database info of '" . $modName . "' (a " . $type . ")");

        $dbconn = $xar->db()->getConn();
        $tables = $xar->db()->getTables();

        // theme+s or module+s
        if (!isset($tables[$type . 's'])) {
            $this->loadDbInfo($type . 's', $type . 's');
            $tables = $xar->db()->getTables();
        }
        $table = $tables[$type . 's'];

        if ($type == 'theme') {
            $query = "SELECT items.regid, items.directory,
                         items.id, items.version, items.state, items.name, items.configuration
                  FROM   $table items
                  WHERE  items.name = ? OR items.directory = ?";
        } else {
            $query = "SELECT items.regid, items.directory,
                         items.id, items.version, items.state, items.name
                  FROM   $table items
                  WHERE  items.name = ? OR items.directory = ?";
        }
        $bindvars = [$modName, $modName];
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, $xar->db()->getFetchNum());

        if (!$result->next()) {
            $result->close();
            return [];
        }

        $modBaseInfo = [];
        if ($type == 'theme') {
            [$regid, $directory, $systemid, $version, $state, $name, $configuration] = $result->getRow();
        } else {
            [$regid, $directory, $systemid, $version, $state, $name] = $result->getRow();
        }
        $result->close();

        $modBaseInfo['regid'] = (int) $regid;
        $modBaseInfo['systemid'] = (int) $systemid;
        $modBaseInfo['version'] = $version;
        $modBaseInfo['state'] = (int) $state;
        $modBaseInfo['name'] = $name;
        $modBaseInfo['directory'] = $directory;
        $modBaseInfo['displayname'] = $this->getDisplayName($directory, $type);
        $modBaseInfo['displaydescription'] = $this->getDisplayDescription($directory, $type);
        // Shortcut for os prepared directory
        // TODO: <marco> get rid of it since useless
        $modBaseInfo['osdirectory'] = $xar->prep()->path($directory);
        if ($type == 'theme') {
            try {
                $modBaseInfo['configuration'] = unserialize($configuration);
            } catch (Exception $e) {
                $modBaseInfo['configuration'] = [];
            }
        }

        // This needed?
        if (empty($modBaseInfo['state'])) {
            $modBaseInfo['state'] = ixarMod::STATE_UNINITIALISED;
        }
        $xar->mem()->set($cacheCollection, $name, $modBaseInfo);

        return $modBaseInfo;
    }

    /** @return array<string, mixed> */
    public function getInfo(int $modRegId, $type = 'module'): array
    {
        if (empty($modRegId)) {
            throw new EmptyParameterException('modRegid');
        }

        $xar = $this->getParent();
        switch ($type) {
            case 'module':
                if ($xar->mem()->has('Mod.Infos', $modRegId)) {
                    return $xar->mem()->get('Mod.Infos', $modRegId);
                }
                break;
            case 'theme':
                if ($xar->mem()->has('Theme.Infos', $modRegId)) {
                    return $xar->mem()->get('Theme.Infos', $modRegId);
                }
                break;
            default:
                throw new BadParameterException('module/theme type');
        }
        // Log it when it doesn't come from the cache
        $xar->log()->debug("xar::mod()->getInfo: Getting database info of ID '" . $modRegId . "' (a " . $type . ")");

        $dbconn = $xar->db()->getConn();
        $tables = $xar->db()->getTables();

        if (!isset($tables['modules'])) {
            $this->loadDbInfo('modules', 'modules');
            $tables = $xar->db()->getTables();
        }

        switch ($type) {
            case 'module':
            default:
                $the_table = $tables['modules'];
                $query = "SELECT id,
                             name,
                             directory,
                             version,
                             admin_capable,
                             user_capable,
                             state
                       FROM  $the_table WHERE regid = ?";
                break;
            case 'theme':
                $the_table = $tables['themes'];
                $query = "SELECT id,
                             name,
                             directory,
                             version,
                             configuration,
                             state
                       FROM  $the_table WHERE regid = ?";
                break;
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([$modRegId], $xar->db()->getFetchNum());

        if (!$result->next()) {
            $result->close();
            throw new IDNotFoundException($modRegId);
        }

        $modInfo = [];
        switch ($type) {
            case 'module':
            default:
                [$modInfo['systemid'],
                    $modInfo['name'],
                    $modInfo['directory'],
                    $modInfo['version'],
                    $modInfo['admincapable'],
                    $modInfo['usercapable'],
                    $modInfo['state']] = $result->getRow();
                break;
            case 'theme':
                [$modInfo['systemid'],
                    $modInfo['name'],
                    $modInfo['directory'],
                    $modInfo['version'],
                    $modInfo['configuration'],
                    $modInfo['state']] = $result->getRow();
                break;
        }
        $result->close();
        unset($result);

        $modInfo['regid'] = (int) $modRegId;
        $modInfo['displayname'] = $this->getDisplayName($modInfo['name'], $type);
        $modInfo['displaydescription'] = $this->getDisplayDescription($modInfo['name'], $type);
        $modInfo['systemid'] = (int) $modInfo['systemid'];
        $modInfo['state'] = (int) $modInfo['state'];

        // Shortcut for os prepared directory
        $modInfo['osdirectory'] = $xar->prep()->path($modInfo['directory']);

        switch ($type) {
            case 'module':
            default:
                if (!isset($modInfo['state'])) {
                    $modInfo['state'] = ixarMod::STATE_MISSING_FROM_UNINITIALISED;
                } //return; // throw back
                $modFileInfo = $this->getFileInfo($modInfo['osdirectory']);
                break;
            case 'theme':
                if (!isset($modInfo['state'])) {
                    $modInfo['state'] = ixarMod::STATE_MISSING_FROM_UNINITIALISED;
                }
                $modFileInfo = $this->getFileInfo($modInfo['osdirectory'], $type = 'theme');
                break;
        }

        if (empty($modFileInfo)) {
            // We couldn't get file info, fill in unknowns.
            // The exception for this is logged in getFileInfo
            $unknown = $xar->mls()->translate('Unknown');
            $modFileInfo['class'] = $unknown;
            $modFileInfo['description'] = $xar->mls()->translate('This module is not installed properly. Not all info could be retrieved');
            $modFileInfo['category'] = $unknown;
            $modFileInfo['displayname'] = $unknown;
            $modFileInfo['displaydescription'] = $unknown;
            $modFileInfo['author'] = $unknown;
            $modFileInfo['contact'] = $unknown;
            $modFileInfo['admin'] = $unknown;
            $modFileInfo['user'] = $unknown;
            $modFileInfo['dependency'] = [];
            $modFileInfo['extensions'] = [];

            $modFileInfo['xar_version'] = $unknown;
            $modFileInfo['bl_version'] = $unknown;
            $modFileInfo['homepage'] = $unknown;
            $modFileInfo['email'] = $unknown;
            $modFileInfo['contactinfo'] = $unknown;
            $modFileInfo['publishdate'] = $unknown;
            $modFileInfo['license'] = $unknown;
        }

        $modInfo = array_merge($modFileInfo, $modInfo);

        switch ($type) {
            case 'module':
            default:
                $xar->mem()->set('Mod.Infos', $modRegId, $modInfo);
                break;
            case 'theme':
                $xar->mem()->set('Theme.Infos', $modRegId, $modInfo);
                break;
        }
        return $modInfo;
    }

    /** @return array<string, mixed> */
    public function getTables(string $modName): array
    {
        $result = xarClassMap::findTables($modName);
        if (!empty($result) && class_exists($result['classname'])) {
            $tablesCall = new $result['classname']();
            // @todo pass along the DB prefix to $tablesCall
            return $tablesCall();
        }

        // Load the database definition if required
        try {
            include_once sys::code() . 'modules/' . $modName . '/xartables.php';
        } catch (Exception $e) {
            return [];
        }
        $tablefunc = $modName . '_' . 'xartables';
        if (function_exists($tablefunc)) {
            // @todo pass along the DB prefix to $tablefunc
            return $tablefunc();
        }
        return [];
    }

    public function loadDbInfo(string $modName, ?string $modDir = null): mixed
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        // @checkme force this here
        $modDir ??= $modName;

        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        // Check to ensure we aren't doing this twice
        if (isset($this->loadedDbInfoCache[$modName])) {
            return true;
        }
        $xar = $this->getParent();

        $result = xarClassMap::findTables($modName);
        if (!empty($result) && class_exists($result['classname'])) {
            $tablesCall = new $result['classname']();
            // pass along the DB prefix to $tablesCall
            $xar->db()->importTables($tablesCall($xar->db()->getPrefix()));
            $this->loadedDbInfoCache[$modName] = true;
            return true;
        }

        // For base and modules, which don't have a xartables - CHECKME: why not again ?
        if (!file_exists(sys::code() . 'modules/' . $modDir . '/xartables.php')) {
            // set anyway, so we don't try over and over
            $this->loadedDbInfoCache[$modName] = false;
            return false;
        }

        // Load the database definition if required
        try {
            include_once sys::code() . 'modules/' . $modDir . '/xartables.php';
        } catch (Exception $e) {
            // set anyway, so we don't try over and over
            $this->loadedDbInfoCache[$modName] = false;
            return false;
        }

        $tablefunc = $modName . '_' . 'xartables';
        if (function_exists($tablefunc)) {
            // pass along the DB prefix to $tablefunc
            $xar->db()->importTables($tablefunc($xar->db()->getPrefix()));
        }

        $this->loadedDbInfoCache[$modName] = true;
        return true;
    }

    public function isAvailable(string $modName, $type = 'module'): bool
    {
        // FIXME: there is no point to the cache here, since
        // xar::mod()->getBaseInfo() caches module details anyway.

        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        // Get the real module details.
        // The module details will be cached anyway.
        $modBaseInfo = $this->getBaseInfo($modName, $type);

        // Return false if the result wasn't set
        if (empty($modBaseInfo)) {
            return false;
        } // throw back

        if (!empty($this->noCacheMod) || !isset($this->modAvailableCache[$modBaseInfo['name']])) {
            // We should be ok now, return the state of the module
            $modState = $modBaseInfo['state'];
            $this->modAvailableCache[$modBaseInfo['name']] = false;

            if ($modState == ixarMod::STATE_ACTIVE) {
                $this->modAvailableCache[$modBaseInfo['name']] = true;
            }
        }
        return $this->modAvailableCache[$modBaseInfo['name']];
    }

    public function getIds($modName, $type = 'module')
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        // For themes, kinda weird
        $modBaseInfo = $this->getBaseInfo($modName, $type);
        if (empty($modBaseInfo)) {
            return;
        } // throw back
        return ['systemid' => $modBaseInfo['systemid'], 'regid' => $modBaseInfo['regid']];
    }

    public function parseFileInfo($version, $name = '')
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

        if (!empty($name)) {
            $xar = $this->getParent();
            $xar->mem()->set('Mod.getFileInfos', $name, $fileInfo);
        }
        return $fileInfo;
    }

    public function checkVersion($modName)
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        $modInfo = $this->getInfo($this->getRegID($modName));
        if (str_contains($modInfo['class'], 'Core')) {
            return $modInfo['version'] == \xarCore::VERSION_NUM;
        } else {
            // Add check for non core modules here
            return true;
        }
    }
}
