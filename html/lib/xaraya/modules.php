<?php

/**
 * Module handling subsystem
 *
 * Eventually we want this to split up in multiple files, for reference:
 * current classes in here (disregarding exceptions):
 *      xarModVars
 *      xarModUserVars
 *      xarMod
 *
 * @package core\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo the double headed theme/module stuff needs to go, a theme is not a module
 */

sys::import("xaraya.context.contexttrait");
sys::import("xaraya.context.context");
sys::import('xaraya.facades.caching');
sys::import('xaraya.facades.config');
sys::import('xaraya.facades.database');
sys::import('xaraya.facades.logger');
sys::import('xaraya.facades.multilanguage');
use Xaraya\Context\ContextInterface;
use Xaraya\Context\Context;
use Xaraya\Facades\xarCache3;
use Xaraya\Facades\xarConfig3;
use Xaraya\Facades\xarDB3;
use Xaraya\Facades\xarLog3;
use Xaraya\Facades\xarMLS3;

/**
 * Exception raised by the modules subsystem
**/
class ModuleBaseInfoNotFoundException extends NotFoundExceptions
{
    protected $message = 'The base info for module "#(1)" could not be found';
}

/**
 * Exception raised by the modules subsystem
**/
class ModuleNotFoundException extends NotFoundExceptions
{
    protected $message = 'A module is missing, the module name could not be determined in the current context';
}

/**
 * Exception raised by the modules subsystem
 * @todo during module init(), any GUI hook functions registered will throw this
**/
class ModuleNotActiveException extends xarExceptions
{
    protected $message = 'The module "#(1)" was called, but it is not active.';
}

/**
 * Flags for loading APIs
 * @deprecated 2.6.2 moved to class constants
 */
//define('XARMOD_LOAD_ONLYACTIVE', 1);
//define('XARMOD_LOAD_ANYSTATE', 2);

/*
    Bring in the module variables to maintain interface compatibility for now
*/
sys::import('xaraya.variables.module');
sys::import('xaraya.variables.moduser');

/**
 * Interface declaration for xarMod
 *
 * @todo this is very likely to change, it was created as baseline for refactoring
 */
interface IxarMod {}

/**
 * Preliminary class to model xarMod interface
 *
 * @package core\modules
 */
class xarMod extends xarObject implements IxarMod
{
    public const LOAD_UNDEFINED                   = 0;
    public const LOAD_ONLYACTIVE                  = 1;
    public const LOAD_ANYSTATE                    = 2;
    public const STATE_UNINITIALISED              = 1;
    public const STATE_INACTIVE                   = 2;
    public const STATE_ACTIVE                     = 3;
    public const STATE_MISSING_FROM_UNINITIALISED = 4;
    public const STATE_UPGRADED                   = 5;
    public const STATE_ANY                        = 0;
    public const STATE_INSTALLED                  = 6;
    public const STATE_MISSING_FROM_INACTIVE      = 7;
    public const STATE_MISSING_FROM_ACTIVE        = 8;
    public const STATE_MISSING_FROM_UPGRADED      = 9;
    public const STATE_ERROR_UNINITIALISED        = 10;
    public const STATE_ERROR_INACTIVE             = 11;
    public const STATE_ERROR_ACTIVE               = 12;
    public const STATE_ERROR_UPGRADED             = 13;

    public static $genShortUrls = false;
    public static $genXmlUrls   = true;
    public static $noCacheState = false;
    /** @var array<string, object> */
    private static $moduleClasses = [];
    protected static bool $initialized = false;

    /**
     * Initialize
     *
     */
    public static function init(array $args = [])
    {
        if (empty($args)) {
            if (self::$initialized) {
                return true;
            }
            $args = self::getConfig();
        }
        self::$genShortUrls = $args['enableShortURLsSupport'];
        self::$genXmlUrls   = $args['generateXMLURLs'];

        // Register the events for this subsystem
        // events are now registered during modules module init
        //xarEvents::register('ModLoad');
        //xarEvents::register('ModAPILoad');

        // Modules Support Tables
        $prefix = xarDB3::getPrefix();

        // How we want it
        $tables['modules']         = $prefix . '_modules';
        $tables['module_vars']     = $prefix . '_module_vars';
        $tables['module_itemvars'] = $prefix . '_module_itemvars';
        $tables['hooks']           = $prefix . '_hooks';
        $tables['themes']          = $prefix . '_themes';

        xarDB3::importTables($tables);
        self::$initialized = true;
        return true;
    }

    public static function getConfig()
    {
        $systemArgs = ['enableShortURLsSupport' => xarConfig3::getVar('Site.Core.EnableShortURLsSupport'),
            'generateXMLURLs' => true];
        return $systemArgs;
    }

    /**
     * Get name of a module
     *
     * If regID is passed in, return the name of that module, otherwise use
     * current toplevel module.
     *
     * @param int|null $regID optional regID for module
     * @return string the name of the current top-level module
     */
    public static function getName($regID = null)
    {
        if (!isset($regID)) {
            $modName = xarController::getRequest()->getModule();
        } else {
            $modinfo = self::getInfo($regID);
            $modName = $modinfo['name'];
        }
        assert(!empty($modName));
        return $modName;
    }

    /**
     * Get the displayable name for modName
     *
     * The displayable name is sensible to user language.
     *
     * @param string $modName registered name of module
     * @param string $type determines theme or module
     * @return string the displayable name
     * @todo   re-evaluate this, i think it causes more harm than joy
     */
    public static function getDisplayName($modName = null, $type = 'module')
    {
        if (empty($modName)) {
            $modName = self::getName();
        }
        $modInfo = self::getFileInfo($modName, $type);
        //print_r($modName . '=' . http_build_query($modInfo) . "<br>\n");
        if (empty($modInfo['displayname'])) {
            $modInfo['displayname'] = $modName;
        }
        return xarMLS3::translate($modInfo['displayname']);
    }

    /**
     * Get the displayable description for modName
     *
     * The displayable description is sensible to user language.
     *
     * @param string $modName registered name of module
     * @param string $type determines theme or module
     * @return string the displayable description
     */
    public static function getDisplayDescription($modName = null, $type = 'module')
    {
        if (empty($modName)) {
            $modName = self::getName();
        }

        $modInfo = self::getFileInfo($modName, $type);
        if (empty($modInfo['displaydescription'])) {
            $modInfo['displaydescription'] = $modName;
        }
        return xarMLS3::translate($modInfo['displaydescription']);
    }

    /**
     * Temporary helper function during regid->systemid migration
     *
     * @todo once the migration is done, migrate this out.
     */
    private static function getIds($modName, $type = 'module')
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        // For themes, kinda weird
        $modBaseInfo = self::getBaseInfo($modName, $type);
        if (!isset($modBaseInfo)) {
            return;
        } // throw back
        return ['systemid' => $modBaseInfo['systemid'], 'regid' => $modBaseInfo['regid']];
    }

    /**
     * Get module registry ID by name
     *
     * @param string $modName The name of the module
     * @param string $type determines theme or module
     * @return int|null The module registry ID.
     */
    public static function getRegID($modName, $type = 'module')
    {
        $ids = self::getIds($modName, $type);
        return (isset($ids['regid']) && !is_null($ids['regid'])) ? (int) $ids['regid'] : null;
    }

    /**
     * Get module system ID by name
     *
     * @param string $modName The name of the module
     * @return int|void The module registry ID.
     */
    public static function getID($modName)
    {
        $ids = self::getIds($modName);
        if (!isset($ids) || !isset($ids['systemid'])) {
            return;
        }
        return (int) $ids['systemid'];
    }

    /**
     * Get the module's current state
     *
     * @param int $modRegId the module's registered id
     * @param string $type determines theme or module
     * @return mixed the module's current state
     * @deprecated 2.4.0 We dont need this, used nowhere
     */
    public static function getState($modRegId, $type = 'module')
    {
        $tmp = self::getInfo($modRegId, $type);
        return (int) $tmp['state'];
    }

    /**
     * Check if a module is installed and its state is STATE_ACTIVE
     *
     * @static $modAvailableCache array
     * @param string $modName registered name of module
     * @param string $type determines theme or module
     * @return bool true if the module is available
     */
    public static function isAvailable($modName, $type = 'module')
    {
        //xarLog3::debug("xarMod::isAvailable: begin $type:$modName");

        // FIXME: there is no point to the cache here, since
        // xarMod::getBaseInfo() caches module details anyway.
        static $modAvailableCache = [];

        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        // Get the real module details.
        // The module details will be cached anyway.
        $modBaseInfo = self::getBaseInfo($modName, $type);

        // Return false if the result wasn't set
        if (!isset($modBaseInfo)) {
            return false;
        } // throw back

        if (!empty(self::$noCacheState) || !isset($modAvailableCache[$modBaseInfo['name']])) {
            // We should be ok now, return the state of the module
            $modState = $modBaseInfo['state'];
            $modAvailableCache[$modBaseInfo['name']] = false;

            if ($modState == self::STATE_ACTIVE) {
                $modAvailableCache[$modBaseInfo['name']] = true;
            }
        }
        //xarLog3::debug("xarMod::isAvailable: end $type:$modName");
        return $modAvailableCache[$modBaseInfo['name']];
    }

    /**
     * Get information on module
     *
     * @param int $modRegId module id
     * @param string $type determines theme or module
     * @return array<mixed> of module information
     * @throws EmptyParameterException
     * @throws BadParameterException
     * @throws IDNotFoundException
     */
    public static function getInfo($modRegId, $type = 'module')
    {
        if (empty($modRegId)) {
            throw new EmptyParameterException('modRegid');
        }

        switch ($type) {
            case 'module':
                if (xarCoreCache::isCached('Mod.Infos', $modRegId)) {
                    return xarCoreCache::getCached('Mod.Infos', $modRegId);
                }
                break;
            case 'theme':
                if (xarCoreCache::isCached('Theme.Infos', $modRegId)) {
                    return xarCoreCache::getCached('Theme.Infos', $modRegId);
                }
                break;
            default:
                throw new BadParameterException('module/theme type');
        }
        // Log it when it doesn't come from the cache
        xarLog3::debug("xarMod::getInfo: Getting database info of ID '" . $modRegId . "' (a " . $type . ")");

        $dbconn = xarDB3::getConn();
        $tables = xarDB3::getTables();

        if (!isset($tables['modules'])) {
            self::loadDbInfo('modules', 'modules');
            $tables = xarDB3::getTables();
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
        $result = $stmt->executeQuery([$modRegId], xarDB3::getFetchNum());

        if (!$result->next()) {
            $result->close();
            throw new IDNotFoundException($modRegId);
        }

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
        $modInfo['displayname'] = self::getDisplayName($modInfo['name'], $type);
        $modInfo['displaydescription'] = self::getDisplayDescription($modInfo['name'], $type);
        $modInfo['systemid'] = (int) $modInfo['systemid'];
        $modInfo['state'] = (int) $modInfo['state'];

        // Shortcut for os prepared directory
        $modInfo['osdirectory'] = xarVar::prepForOS($modInfo['directory']);

        switch ($type) {
            case 'module':
            default:
                if (!isset($modInfo['state'])) {
                    $modInfo['state'] = self::STATE_MISSING_FROM_UNINITIALISED;
                } //return; // throw back
                $modFileInfo = self::getFileInfo($modInfo['osdirectory']);
                break;
            case 'theme':
                if (!isset($modInfo['state'])) {
                    $modInfo['state'] = self::STATE_MISSING_FROM_UNINITIALISED;
                }
                $modFileInfo = self::getFileInfo($modInfo['osdirectory'], $type = 'theme');
                break;
        }

        if (!isset($modFileInfo)) {
            // We couldn't get file info, fill in unknowns.
            // The exception for this is logged in getFileInfo
            $unknown = xarMLS3::translate('Unknown');
            $modFileInfo['class'] = $unknown;
            $modFileInfo['description'] = xarMLS3::translate('This module is not installed properly. Not all info could be retrieved');
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
            $modFileInfo['class'] = $unknown;
            $modFileInfo['author'] = $unknown;
            $modFileInfo['homepage'] = $unknown;
            $modFileInfo['email'] = $unknown;
            $modFileInfo['description'] = $unknown;
            $modFileInfo['contactinfo'] = $unknown;
            $modFileInfo['publishdate'] = $unknown;
            $modFileInfo['license'] = $unknown;
        }

        $modInfo = array_merge($modFileInfo, $modInfo);

        switch ($type) {
            case 'module':
            default:
                xarCoreCache::setCached('Mod.Infos', $modRegId, $modInfo);
                break;
            case 'theme':
                xarCoreCache::setCached('Theme.Infos', $modRegId, $modInfo);
                break;
        }
        return $modInfo;
    }

    /**
     * Load a module's base information
     *
     * @param string $modName the module's name
     * @param string $type determines theme or module
     * @return mixed an array of base module info on success
     * @throws EmptyParameterException
     * @throws BadParameterException
     */
    public static function getBaseInfo($modName, $type = 'module')
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        if ($type != 'module' && $type != 'theme') {
            throw new BadParameterException($type, 'The value of the "type" parameter must be "module" or "theme", it was "#(1)"');
        }

        // The self::$noCacheState flag tells Xaraya *not*
        // to cache module (+state) where this would lead to problems
        // like in the installer for example.
        if ($type == 'module') {
            $cacheCollection = 'Mod.BaseInfos';
            $checkNoState = xarMod::$noCacheState;
        } else {
            $cacheCollection = 'Theme.BaseInfos';
            $checkNoState = xarTheme::$noCacheState;
        }

        if (empty($checkNoState) && xarCoreCache::isCached($cacheCollection, $modName)) {
            return xarCoreCache::getCached($cacheCollection, $modName);
        }
        // Log it when it doesnt come from the cache
        xarLog3::debug("xarMod::getBaseInfo: Getting database info of '" . $modName . "' (a " . $type . ")");

        $dbconn = xarDB3::getConn();
        $tables = xarDB3::getTables();

        // theme+s or module+s
        if (!isset($tables[$type . 's'])) {
            self::loadDbInfo($type . 's', $type . 's');
            $tables = xarDB3::getTables();
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
        $result = $stmt->executeQuery($bindvars, xarDB3::getFetchNum());

        if (!$result->next()) {
            $result->close();
            return;
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
        $modBaseInfo['displayname'] = xarMod::getDisplayName($directory, $type);
        $modBaseInfo['displaydescription'] = xarMod::getDisplayDescription($directory, $type);
        // Shortcut for os prepared directory
        // TODO: <marco> get rid of it since useless
        $modBaseInfo['osdirectory'] = xarVar::prepForOS($directory);
        if ($type == 'theme') {
            try {
                $modBaseInfo['configuration'] = unserialize($configuration);
            } catch (Exception $e) {
                $modBaseInfo['configuration'] = [];
            }
        }

        // This needed?
        if (empty($modBaseInfo['state'])) {
            $modBaseInfo['state'] = self::STATE_UNINITIALISED;
        }
        xarCoreCache::setCached($cacheCollection, $name, $modBaseInfo);

        return $modBaseInfo;
    }

    /**
     * Get info from xarversion.php for module specified by modOsDir
     *
     * @param string $modOsDir the module's directory
     * @param string $type determines theme or module
     * @return array<string, mixed>|void an array of module file information
     * @throws EmptyParameterException
     * @throws BadParameterException
     * @todo <marco> #1 FIXME: admin or admin capable?
     */
    public static function getFileInfo($modOsDir, $type = 'module')
    {
        if (empty($modOsDir)) {
            throw new EmptyParameterException('modOsDir');
        }

        if (empty(self::$noCacheState) && xarCoreCache::isCached('Mod.getFileInfos', $modOsDir . " / " . $type)) {
            return xarCoreCache::getCached('Mod.getFileInfos', $modOsDir . " / " . $type);
        }
        // Log it when it didnt came from cache
        xarLog3::debug("xarMod::getFileInfo: Getting file info of '" . $modOsDir . "' (a " . $type . ")");


        // TODO redo legacy support via type.
        switch ($type) {
            case 'module':
                // Spliffster, additional mod info from modules/$modDir/xarversion.php
                $fileName = sys::code() . 'modules/' . $modOsDir . '/xarversion.php';
                $part = 'xarversion';
                // If the locale is already present, it means we can make the translations available
                if (!empty(xarMLS3::getCurrentLocale())) {
                    xarMLS3::loadModuleTranslations($modOsDir, '', 'version');
                }
                break;
            case 'property':
                $fileName = sys::code() . 'properties/' . $modOsDir . '/main.php';
                $part = 'main';
                break;
            case 'block':
                $fileName = sys::code() . 'blocks/' . $modOsDir . '/' . $modOsDir . '.php';
                $part = $modOsDir;
                break;
            case 'theme':
                $fileName = xarConfig3::getVar('Site.BL.ThemesDirectory') . '/' . $modOsDir . '/xartheme.php';
                $part = 'xartheme';
                break;
            default:
                throw new BadParameterException('module/theme type');
        }

        if (!file_exists($fileName)) {
            // Don't raise an exception, it is too harsh, but log it tho (bug 295)
            xarLog3::warning("xarMod::getFileInfo: Could not find xarversion.php, skipping $modOsDir");
            // throw new FileNotFoundException($fileName);
            return;
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

        // name and id are required, assert them, otherwise the module is invalid
        assert(isset($version["name"]) && isset($version["id"]));
        $FileInfo['name']           = $version['name'];
        $FileInfo['regid']          = (int) $version['id'];
        $FileInfo['displayname']    = $version['displayname'] ?? $version['name'];
        $FileInfo['description']    = $version['description'] ?? false;
        $FileInfo['displaydescription'] = $version['displaydescription'] ?? $FileInfo['description'];
        $FileInfo['admin']          = isset($version['admin']) ? (bool) $version['admin'] : false;
        $FileInfo['admin_capable']  = isset($version['admin']) ? (bool) $version['admin'] : false;
        $FileInfo['user']           = isset($version['user']) ? (bool) $version['user'] : false;
        $FileInfo['user_capable']   = isset($version['user']) ? (bool) $version['user'] : false;
        $FileInfo['securityschema'] = $version['securityschema'] ?? false;
        $FileInfo['class']          = $version['class'] ?? false;
        $FileInfo['category']       = $version['category'] ?? false;
        $FileInfo['locale']         = $version['locale'] ?? 'en_US.iso-8859-1';
        $FileInfo['author']         = $version['author'] ?? false;
        $FileInfo['contact']        = $version['contact'] ?? false;
        $FileInfo['dependency']     = $version['dependency'] ?? [];
        $FileInfo['dependencyinfo'] = $version['dependencyinfo'] ?? [];
        $FileInfo['propertyinfo']   = $version['propertyinfo'] ?? [];
        $FileInfo['extensions']     = $version['extensions'] ?? [];
        $FileInfo['directory']      = $version['directory'] ?? false;
        $FileInfo['homepage']       = $version['homepage'] ?? false;
        $FileInfo['email']          = $version['email'] ?? false;
        $FileInfo['contact_info']   = $version['contact_info'] ?? false;
        $FileInfo['publish_date']   = $version['publish_date'] ?? false;
        $FileInfo['license']        = $version['license'] ?? false;
        $FileInfo['version']        = $version['version'] ?? false;
        // Check that 'xar_version' key exists before assigning
        if (!$FileInfo['version'] && isset($version['xar_version'])) {
            $FileInfo['version'] = $version['xar_version'];
        }
        $FileInfo['bl_version']     = $version['bl_version'] ?? false;
        $FileInfo['namespace']      = $version['namespace'] ?? '';
        $FileInfo['twigtemplates']  = $version['twigtemplates'] ?? false;
        $FileInfo['twigextension']  = $version['twigextension'] ?? '.html.twig';

        xarCoreCache::setCached('Mod.getFileInfos', $modOsDir . " / " . $type, $FileInfo);
        return $FileInfo;
    }

    /**
     * Load database definition for a module
     *
     * @param string $modName name of module to load database definition for
     * @param string|null $modDir directory that module is in
     * @param string $type module or theme
     * @return mixed true on success
     * @throws EmptyParameterException
     */
    public static function loadDbInfo($modName, $modDir = null, $type = 'module')
    {
        static $loadedDbInfoCache = [];

        if ($type == 'theme') {
            return true;
        } // sigh.

        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        // Check to ensure we aren't doing this twice
        if (isset($loadedDbInfoCache[$modName])) {
            return true;
        }

        // Get the directory if we don't already have it
        if (empty($modDir)) {
            $modBaseInfo = self::getBaseInfo($modName, $type);
            if (!isset($modBaseInfo)) {
                return;
            } // throw back
            $modDir = xarVar::prepForOS($modBaseInfo['directory']);
        } else {
            $modDir = xarVar::prepForOS($modDir);
        }

        // For base and modules, which don't have a xartables - CHECKME: why not again ?
        if (!file_exists(sys::code() . 'modules/' . $modDir . '/xartables.php')) {
            // set anyway, so we don't try over and over
            $loadedDbInfoCache[$modName] = false;
            return false;
        }

        // Load the database definition if required
        try {
            sys::import('modules.' . $modDir . '.xartables');
        } catch (Exception $e) {
            // set anyway, so we don't try over and over
            $loadedDbInfoCache[$modName] = false;
            return false;
        }

        $tablefunc = $modName . '_' . 'xartables';
        if (function_exists($tablefunc)) {
            // pass along the DB prefix to $tablefunc
            xarDB3::importTables($tablefunc(xarDB3::getPrefix()));
        }

        $loadedDbInfoCache[$modName] = true;
        return true;
    }

    /**
     * Call a module GUI function.
     *
     * Ex: modName_modType_funcName($args, $context);
     *
     * @param string $modName registered name of module
     * @param string $modType type of function to run
     * @param string $funcName specific function to run
     * @param array<string, mixed> $args arguments to pass to the function
     * @param ?Context<string, mixed> $context optional context for the function call (default = none)
     * @return mixed The output of the function, or raise an exception
     */
    public static function guiFunc($modName, $modType = 'user', $funcName = 'main', $args = [], $context = null)
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        // Get a cache key for this module function if it's suitable for module caching
        $cacheKey = xarCache3::getModuleKey($modName, $modType, $funcName, $args);

        // Check if the module function is cached
        if (xarCache3::hasModule($cacheKey)) {
            // Return the cached module function output
            return xarCache3::getModule($cacheKey);
        }
        if (!isset($context)) {
            $context = new Context(['source' => __METHOD__]);
        }
        // Set module name and type in context if needed
        $context['module'] ??= $modName;
        $context['modtype'] ??= $modType;
        // @todo call module gui class method directly if available
        $tplData = self::callFunc($modName, $modType, $funcName, $args, '', $context);
        // If we have a string of data, we assume someone else did xarTpl* for us
        if (!is_array($tplData)) {
            // Set the output of the module function in cache
            xarCache3::setModule($cacheKey, $tplData);
            return $tplData;
        }

        // See if we have a special template to apply
        $templateName = null;
        if (isset($tplData['_bl_template'])) {
            $templateName = $tplData['_bl_template'];
        }

        // @todo Pass along the context for xarTpl::module() if needed
        $tplData['context'] ??= $context;

        // Create the output.
        $tplOutput = xarTpl::module($modName, $modType, $funcName, $tplData, $templateName);

        // Set the output of the module function in cache
        xarCache3::setModule($cacheKey, $tplOutput);

        return $tplOutput;
    }

    /**
     * Call a module API function.
     *
     * Using the modules name, type, func, and optional arguments
     * builds a function name by joining them together
     * and using the optional arguments as parameters
     * like so:
     * Ex: modName_modTypeapi_funcName($args, $context);
     *
     * @param string $modName registered name of module
     * @param string $modType type of function to run
     * @param string $funcName specific function to run
     * @param array<string, mixed> $args arguments to pass to the function
     * @param ?Context<string, mixed> $context optional context for the function call (default = none)
     * @return mixed The output of the function, or false on failure
     */
    public static function apiFunc($modName, $modType = 'user', $funcName = 'main', $args = [], $context = null)
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        if (!isset($context)) {
            $context = new Context(['source' => __METHOD__]);
        }
        // @todo call module api class method directly if available
        return self::callfunc($modName, $modType, $funcName, $args, 'api', $context);
    }

    /**
     * Work horse method for the lazy calling of module functions
     * @param ?Context<string, mixed> $context optional context for the function call (default = none)
     */
    private static function callFunc($modName, $modType, $funcName, $args, $funcType = '', $context = null)
    {
        assert(($funcType == "api" or $funcType == ""));

        // Build function name
        $modFunc = "{$modName}_{$modType}{$funcType}_{$funcName}";
        if (empty($modName) || empty($funcName)) {
            // This is not a valid function syntax - CHECKME: also for api functions ?
            if ($funcType == "api") {
                throw new FunctionNotFoundException($modFunc);
            } else {
                return xarController::notFound('Function not found', $context);
            }
        }

        // good thing this information is cached :)
        $modBaseInfo = self::getBaseInfo($modName);
        if (!isset($modBaseInfo)) {
            // This is not a valid module - CHECKME: also for api functions ?
            if ($funcType == "api") {
                throw new FunctionNotFoundException($modFunc);
            } else {
                return xarController::notFound('Function not found', $context);
            }
        }

        // Call function
        $found = true;
        $isLoaded = true;
        $msg = '';
        if (!function_exists($modFunc)) {
            // attempt to load the module's api - this will load xaruserapi.php or xaruser.php etc. if they exist
            if ($funcType == 'api') {
                xarMod::apiLoad($modName, $modType);
            } else {
                try {
                    xarMod::load($modName, $modType);
                } catch (Exception $e) {
                    return xarController::notFound('Function not found', $context);
                }
            }

            xarLog3::info("xarMod::callFunc: Calling $modFunc");

            // let's check for that function again to be sure
            if (!function_exists($modFunc)) {
                // Q: who are we kidding with this? osdirectory == modName always, no?
                $funcFile = sys::code() . 'modules/' . $modBaseInfo['osdirectory'] . '/xar' . $modType . $funcType . '/' . strtolower($funcName) . '.php';
                if (!file_exists($funcFile)) {
                    // @todo cache this if we ever get here again? Already cached internally for module class methods
                    // Note: pass modType . funcType as modType here for module classes, and use funcType to identify the callType (api or not)
                    $callable = self::getModuleClassMethod($modName, $modType . $funcType, $funcName, $funcType);
                    if (!empty($callable)) {
                        // this expects an instance in $callable[0]
                        if (is_array($callable) && is_a($callable[0] ?? '', ContextInterface::class)) {
                            $context?->tracePath($callable[0]::class . '::' . $callable[1], $args);
                            $callable[0]->setContext($context);
                        }
                        $funcResult = $callable($args);
                        return $funcResult;
                    }
                    // Valid syntax, but the function doesn't exist
                    if ($funcType == "api") {
                        throw new FunctionNotFoundException($modFunc);
                    } else {
                        return xarController::notFound('Function not found', $context);
                    }
                } else {
                    ob_start();
                    $r = sys::import('modules.' . $modName . '.xar' . $modType . $funcType . '.' . strtolower($funcName));
                    $error_msg = strip_tags(ob_get_contents());
                    ob_end_clean();

                    if (empty($r) || !$r) {
                        $msg = "Could not load function file: [#(1)].\n\n Error Caught:\n #(2)";
                        $params = [$funcFile, $error_msg];
                        $isLoaded = false;
                    }
                    if (!function_exists($modFunc)) {
                        $found = false;
                    }
                }
            }

            if ($found) {
                // Load the translations file, only if we have loaded the API function for the first time here.
                if (xarMLS3::loadModuleTranslations($modName, $modType . $funcType, $funcName) === null) {
                    return;
                }
            }
        }

        if (!$found) {
            return xarController::notFound('Function not found', $context);
        }
        $context?->tracePath(__METHOD__ . ': ' . $modFunc, $args);

        $funcResult = $modFunc($args, $context);
        return $funcResult;
    }

    /**
     * Load the modType of module identified by modName.
     *
     * @param string $modName name of module to load
     * @param string $modType type of functions to load
     * @param int $flags flags to modify function behaviour (default LOAD_ONLYACTIVE)
     * @return mixed
     */
    public static function load($modName, $modType = 'user', $flags = self::LOAD_ONLYACTIVE)
    {
        return self::privateLoad($modName, $modType, $flags);
    }

    /**
     * Load the modType API for module identified by modName.
     *
     * @param string $modName registered name of the module
     * @param string $modType type of functions to load
     * @param int $flags flags to modify function behaviour (default LOAD_ANYSTATE)
     * @return mixed true on success
     */
    public static function apiLoad($modName, $modType = 'user', $flags = self::LOAD_ANYSTATE)
    {
        return self::privateLoad($modName, $modType . 'api', $flags);
    }

    /**
     * Load the modType of module identified by modName.
     *
     * @static $loadedModuleCache
     * @param string $modName name of module to load
     * @param string $modType type of functions to load
     * @param int $flags flags to modify function behaviour
     * @return mixed
     * @throws EmptyParameterException
     * @throws ModuleNotFoundException
     * @throws ModuleNotActiveException
     */
    private static function privateLoad($modName, $modType, $flags = self::LOAD_UNDEFINED)
    {
        static $loadedModuleCache = [];
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        // Make sure we access the cache with lower case key, return true when we already loaded
        $cacheKey = strtolower($modName . $modType);
        if (isset($loadedModuleCache[$cacheKey])) {
            return true;
        }

        // Log it when it doesn't come from the cache
        xarLog3::debug("xarMod::load: Loading $modName:$modType");

        $modBaseInfo = self::getBaseInfo($modName);
        // Not a valid module - throw exception
        if (!isset($modBaseInfo)) {
            throw new ModuleNotFoundException($modName);
        }

        // Not a valid module state - throw exception
        if ($modBaseInfo['state'] != self::STATE_ACTIVE && !($flags & self::LOAD_ANYSTATE)) {
            throw new ModuleNotActiveException($modName);
        }

        // Not the correct version - throw exception unless we are upgrading
        if (!self::checkVersion($modName) && !xarVar::getCached('Upgrade', 'upgrading') && $modName != 'modules') {
            xarCore::exit('The core module "' . $modName . '" does not have the correct version. Please run the upgrade routine by clicking <a href="upgrade.php">here</a>');
            return false;
        }

        // Load the module files
        $modDir = $modBaseInfo['directory'];
        $fileName = sys::code() . 'modules/' . $modDir . '/xar' . $modType . '.php';

        // Removed the exception.  Causing some weird results with modules without an api.
        // <nuncanada> But now we wont know if something was loaded or not!
        // <nuncanada> We need some way to find it out.
        // Assume failure
        if (file_exists($fileName)) {
            sys::import('modules.' . $modDir . '.xar' . $modType);
            $loadedModuleCache[$cacheKey] = true;
        } elseif (is_dir(sys::code() . 'modules/' . $modDir . '/xar' . $modType)) {
            // this is OK too - do nothing
            $loadedModuleCache[$cacheKey] = true;
        } else {
            // Do we have a module class handling this modType
            $instance = self::getModule($modName);
            // returns null for DefaultModule() = no suitable class type
            $classType = $instance->getClassType($modType);
            if (isset($classType)) {
                // this is OK too - do nothing
                $loadedModuleCache[$cacheKey] = true;
            } else {
                // this is (not really) OK too - do nothing
                $loadedModuleCache[$cacheKey] = false;
            }
        }

        // Load the module translations files (common functions, uncut functions etc.)
        if (xarMLS3::loadModuleTranslations($modName, '', $modType) === null) {
            return;
        }

        // Load database info
        self::loadDbInfo($modName, $modDir);

        // Module loaded successfully, trigger the proper event
        //xarEvents::trigger('ModLoad', $modName);
        if (preg_match('/(.*)?api$/', $modType)) {
            xarEvents::notify('ModApiLoad', $modName);
        } else {
            xarEvents::notify('ModLoad', $modName);
        }
        return true;
    }

    /**
     * Get module class for modName based on defined namespace or ucfirst($modName)
     * @uses \sys::autoload()
     * @param string $modName
     * @return \Xaraya\Modules\ModuleInterface
     */
    public static function getModule($modName)
    {
        if (!array_key_exists($modName, self::$moduleClasses)) {
            sys::autoload();
            $modInfo = self::getFileInfo($modName);
            $namespace = $modInfo['namespace'] ?: 'Xaraya\\Modules\\' . ucfirst($modName);
            $class = $namespace . '\\Module';
            if (class_exists($class)) {
                try {
                    self::$moduleClasses[$modName] = new $class($modName);
                } catch (Throwable $e) {
                    self::$moduleClasses[$modName] = new \Xaraya\Modules\DefaultModule($modName);
                    xarLog3::warning("xarMod::getModule: Error loading $class for module $modName");
                }
            } else {
                self::$moduleClasses[$modName] = new \Xaraya\Modules\DefaultModule($modName);
            }
        }
        return self::$moduleClasses[$modName];
    }

    /**
     * Get module class handling user api functions
     * @param string $modName
     * @return \Xaraya\Modules\UserApiInterface|null
     */
    public static function userapi($modName)
    {
        return self::getModule($modName)->userapi();
    }

    /**
     * Get module class handling user gui functions
     * @param string $modName
     * @return \Xaraya\Modules\UserGuiInterface|null
     */
    public static function usergui($modName)
    {
        return self::getModule($modName)->usergui();
    }

    /**
     * Check if a particular module class method exists, or return null
     *
     * This looks for a module class in $modName handling $modType methods for method $funcName
     * and returns a callable to that method if it exists
     * Ex: [$instance, $funcName] => \Xaraya\Modules\$ModName\$ClassType()->$funcName($args);
     *
     * @param string $modName registered name of module -> used to define namespace
     * @param string $modType type of function to run (incl. funcType) -> will be mapped to class type
     * @param string $funcName specific function to run -> find corresponding method
     * @param string $callType is this called as an api function or not -> check against module class
     * @return callable|null
     */
    public static function getModuleClassMethod($modName, $modType, $funcName, $callType = 'api')
    {
        static $methods_cache = [];

        $key = "$modName:$modType:$funcName:$callType";
        if (!array_key_exists($key, $methods_cache)) {
            $instance = self::getModule($modName);
            // returns null for DefaultModule() = no suitable class method
            $methods_cache[$key] = $instance->getCallableMethod($modType, $funcName, $callType);
            if (!isset($methods_cache[$key])) {
                xarLog3::info("xarMod::getModuleClassMethod: Missing method for $key");
            } else {
                // Load the translations file, only if we have loaded the function for the first time here.
                xarMLS3::loadModuleTranslations($modName, $modType, $funcName);
            }
        }
        return $methods_cache[$key];
    }

    /**
     * Check the version of this module against the core version
     *
     * @return boolean
     */
    public static function checkVersion($modName)
    {
        $modInfo = self::getInfo(self::getRegID($modName));
        if ((strpos($modInfo['class'], 'Core') !== false)) {
            return $modInfo['version'] == xarCore::VERSION_NUM;
        } else {
            // Add check for non core modules here
            return true;
        }
    }

    /**
     * Check if a particular module function exists, or default back to 'dynamicdata'
     *
     * @param string $tplmodule optional module where the templates reside
     * @param string $type link type (user, userapi, admin, adminapi, ...)
     * @param string $func link function (display, getitemtypes, ...)
     * @return string tplmodule or 'dynamicdata'
     */
    public static function checkModuleFunction($tplmodule = 'dynamicdata', $type = 'user', $func = 'display', $defaultmodule = 'dynamicdata')
    {
        static $tplmodule_cache = [];

        $key = "$tplmodule:$type:$func";
        if (!isset($tplmodule_cache[$key])) {
            $file = sys::code() . 'modules/' . $tplmodule . '/xar' . $type . '/' . $func . '.php';
            if (file_exists($file)) {
                $tplmodule_cache[$key] = $tplmodule;
                return $tplmodule_cache[$key];
            }
            // Note: pass modType . funcType as modType here for module classes, and use callType (api or not)
            if (str_ends_with($type, 'api')) {
                $callType = 'api';
            } else {
                $callType = 'gui';
                $type .= 'gui';
            }
            $callable = self::getModuleClassMethod($tplmodule, $type, $func, $callType);
            if (!empty($callable)) {
                $tplmodule_cache[$key] = $tplmodule;
            } else {
                $tplmodule_cache[$key] = $defaultmodule;
            }
        }
        return $tplmodule_cache[$key];
    }

    /**
     * Check access for a specific action on module level (see also xarObject and xarBlock)
     *
     * @param string $moduleName the module we want to check access for
     * @param string $action the action we want to take on this module (view/admin) // CHECKME: any others we really use on module level ?
     * @param mixed $roleid override the current user or null
     * @return boolean true if access
     * @throws BadParameterException
     */
    public static function checkAccess($moduleName, $action, $roleid = null)
    {
        // TODO: get module variable with access config: groups, masks, levels or whatever

        // TODO: check for access e.g. by group

        // Fall back on mask-less security check with access levels corresponding to action
        sys::import('modules.privileges.class.security');

        // default actions supported on modules
        switch ($action) {
            case 'admin':
                $seclevel = xarSecurity::ACCESS_ADMIN;
                break;

                // CHECKME: any others we really use on module level (instead of object/item/block/... level) ?

            case 'view':
                $seclevel = xarSecurity::ACCESS_OVERVIEW;
                break;

            default:
                throw new BadParameterException('action', "Supported actions on module level are 'view' and 'admin'");
        }

        if (!empty($roleid)) {
            $role = xarRoles::get($roleid);
            $rolename = $role->getName();
            return xarSecurity::check('', 0, 'All', 'All', $moduleName, $rolename, 0, $seclevel);
        } else {
            return xarSecurity::check('', 0, 'All', 'All', $moduleName, '', 0, $seclevel);
        }
    }
}

/**
 * Interface declaration for module aliases
 *
 */
interface IxarModAlias
{
    public static function resolve($alias);
    public static function set($alias, $modName);
    public static function delete($alias, $modName);
}

/**
 * Class to model interface to module aliases
 *
 * @package core\modules
 * @todo evaluate dependency consequences
 * @todo evaluate usage in modules, it's not very common, as in, perhaps worth to scrap and bolt onto a request mapper
 */
class xarModAlias extends xarObject implements IxarModAlias
{
    /**
     * Resolve an alias for a module
     */
    public static function resolve($alias)
    {
        if ($alias == 'object') {
            return $alias;
        }
        $aliasesMap = xarConfig3::getVar('System.ModuleAliases');
        return (!empty($aliasesMap[$alias])) ? $aliasesMap[$alias] : $alias;
    }

    /**
     * Set an alias for a module
     */
    public static function set($alias, $modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) {
            return;
        }
        $args = ['modName' => $modName, 'aliasModName' => $alias];
        return xarMod::apiFunc('modules', 'admin', 'add_module_alias', $args);
    }

    /**
     * Delete an alias for a module
     */
    public static function delete($alias, $modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) {
            return;
        }
        $args = ['modName' => $modName, 'aliasModName' => $alias];
        return xarMod::apiFunc('modules', 'admin', 'delete_module_alias', $args);
    }
}
