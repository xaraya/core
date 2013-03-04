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
 * @package core
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo Use serialize in module variables?
 * @todo the double headed theme/module stuff needs to go, a theme is not a module
 */

/**
 * Exceptions defined by this subsystem
 *
 */
class ModuleBaseInfoNotFoundException extends NotFoundExceptions
{
    protected $message = 'The base info for module "#(1)" could not be found';
}
/**
**/
class ModuleNotFoundException extends NotFoundExceptions
{
    protected $message = 'A module is missing, the module name could not be determined in the current context';
}
/**
**/
class ModuleNotActiveException extends xarExceptions
{
    protected $message = 'The module "#(1)" was called, but it is not active.';
}

/**
 * State of modules
 * @todo do we really need 13 module states?
 */
define('XARMOD_STATE_UNINITIALISED', 1);
define('XARMOD_STATE_INACTIVE', 2);
define('XARMOD_STATE_ACTIVE', 3);
define('XARMOD_STATE_MISSING_FROM_UNINITIALISED', 4);
define('XARMOD_STATE_UPGRADED', 5);
// This isn't a module state, but only a convenient definition to indicates,
// where it's used, that we don't care about state, any state is good
define('XARMOD_STATE_ANY', 0);
// <andyv> added an extra superficial state, need it for the list filter because
// some ppl requested not to see modules which are not initialised FR/BUG #252
// now we define 'Installed' state as all except 'uninitialised'
// in fact we dont even need a record as it's an exact reverse of state 1
// tell me if there is something wrong with my (twisted) logic ;-)
define('XARMOD_STATE_INSTALLED', 6);
define('XARMOD_STATE_MISSING_FROM_INACTIVE', 7);
define('XARMOD_STATE_MISSING_FROM_ACTIVE', 8);
define('XARMOD_STATE_MISSING_FROM_UPGRADED', 9);
// Bug 1664 - Add  module states for modules that have a db version
// that is greater than the file version
define('XARMOD_STATE_ERROR_UNINITIALISED', 10);
define('XARMOD_STATE_ERROR_INACTIVE', 11);
define('XARMOD_STATE_ERROR_ACTIVE', 12);
define('XARMOD_STATE_ERROR_UPGRADED', 13);

/**
 * Define the theme here for now as well
 *
 */
define('XARTHEME_STATE_UNINITIALISED', 1);
define('XARTHEME_STATE_INACTIVE', 2);
define('XARTHEME_STATE_ACTIVE', 3);
define('XARTHEME_STATE_MISSING_FROM_UNINITIALISED', 4);
define('XARTHEME_STATE_UPGRADED', 5);
define('XARTHEME_STATE_ANY', 0);
define('XARTHEME_STATE_INSTALLED', 6);
define('XARTHEME_STATE_MISSING_FROM_INACTIVE', 7);
define('XARTHEME_STATE_MISSING_FROM_ACTIVE', 8);
define('XARTHEME_STATE_MISSING_FROM_UPGRADED', 9);

/**
 * Flags for loading APIs
 */
define('XARMOD_LOAD_ONLYACTIVE', 1);
define('XARMOD_LOAD_ANYSTATE', 2);

/*
    Bring in the module variables to maintain interface compatibility for now
*/
sys::import('xaraya.variables.module');
sys::import('xaraya.variables.moduser');
/**
 * Wrapper functions to support Xaraya 1 API for modvars and moduservars
**/
function xarModGetVarId($modName, $name)             {   return xarModVars::getID($modName, $name);       }

function xarModDelUserVar($modName, $name, $id=NULL) {   return xarModUserVars::delete($modName, $name, $id);      }

function xarMod__URLencode($data, $type = 'getname')
{
    return xarURL::encode($data, $type);
}

function xarMod__URLnested($args, $prefix)
{
    return xarURL::nested($args, $prefix);
}

function xarMod__URLaddParametersToPath($args, $path, $pini, $psep)
{
    return xarURL::addParametersToPath($args, $path, $pini, $psep);
}

function xarModURL($modName=NULL, $modType='user', $funcName='main', $args=array(), $generateXMLURL=NULL, $fragment=NULL, $entrypoint=array())
{   
    return xarController::URL($modName, $modType, $funcName, $args, $generateXMLURL, $fragment, $entrypoint); 
}

// (Module) Hooks handling subsystem - moved from modules to hooks for (future) clarity

/**
 * Wrapper functions to support Xaraya 1 API for module managment
 *
 */
function xarModGetName()
{   return xarMod::getName(); }

function xarModGetNameFromID($regid)
{   return xarMod::getName($regid); }

function xarModGetDisplayableName($modName = NULL, $type = 'module')
{   return xarMod::getDisplayName($modName, $type); }

function xarModGetDisplayableDescription($modName = NULL, $type = 'module')
{   return xarMod::getDisplayDescription($modName,$type); }

function xarModGetIDFromName($modName, $type = 'module')
{   return xarMod::getRegID($modName, $type); }

function xarModGetInfo($modRegId, $type = 'module')
{   return xarMod::getInfo($modRegId, $type); }

function xarMod_getBaseInfo($modName, $type = 'module')
{   return xarMod::getBaseInfo($modName, $type); }

function xarMod_getFileInfo($modOsDir, $type = 'module')
{   return xarMod::getFileInfo($modOsDir, $type); }

function xarMod__loadDbInfo($modName, $modDir)
{   return xarMod::loadDbInfo($modName, $modDir); }

function xarModDBInfoLoad($modName, $modDir = NULL, $type = 'module')
{   return xarMod::loadDbInfo($modName, $modDir, $type); }

function xarMod_getState($modRegId, $modMode = XARMOD_MODE_PER_SITE, $type = 'module')
{   return xarMod::getState($modRegId, $modMode, $type); }

function xarModIsAvailable($modName, $type = 'module')
{   return xarMod::isAvailable($modName, $type); }

function xarModFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{   return xarMod::guiFunc($modName, $modType, $funcName, $args); }

function xarModAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{   return xarMod::apiFunc($modName, $modType, $funcName, $args,'api'); }

function xarModLoad($modName, $modType = 'user')
{   return xarMod::load($modName, $modType); }

function xarModAPILoad($modName, $modType = 'user')
{   return xarMod::apiLoad($modName, $modType); }

/**
 * Interface declaration for xarMod
 *
 * @todo this is very likely to change, it was created as baseline for refactoring
 */
interface IxarMod
{

}

/**
 * Preliminary class to model xarMod interface
 *
 */
class xarMod extends Object implements IxarMod
{
    static $genShortUrls = false;
    static $genXmlUrls   = true;

    /**
     * Initialize
     *
     */
    static function init(Array $args=array())
    {
        self::$genShortUrls = $args['enableShortURLsSupport'];
        self::$genXmlUrls   = $args['generateXMLURLs'];

        // Register the events for this subsystem
        // events are now registered during modules module init        
        //xarEvents::register('ModLoad');
        //xarEvents::register('ModAPILoad');

        // Modules Support Tables
        $prefix = xarDB::getPrefix();

        // How we want it
        $tables['modules']         = $prefix . '_modules';
        $tables['module_vars']     = $prefix . '_module_vars';
        $tables['module_itemvars'] = $prefix . '_module_itemvars';
        $tables['hooks']           = $prefix . '_hooks';
        $tables['themes']          = $prefix . '_themes';

        xarDB::importTables($tables);
        return true;
    }

    /**
     * Get name of a module
     *
     * If regID is passed in, return the name of that module, otherwise use
     * current toplevel module.
     *
     * 
     * @param  $regID integer optional regID for module
     * @return string the name of the current top-level module
     */
    static function getName($regID = NULL)
    {
        if(!isset($regID)) {
            $modName = xarController::$request->getModule();
        } else {
            $modinfo = self::getInfo($regID);
            $modName = $modinfo['name'];
        }
        assert('!empty($modName)');
        return $modName;
    }

    /**
     * Get the displayable name for modName
     *
     * The displayable name is sensible to user language.
     *
     * 
     * @param modName string registered name of module
     * @return string the displayable name
     * @todo   re-evaluate this, i think it causes more harm than joy
     */
    static function getDisplayName($modName = NULL, $type = 'module')
    {
        if (empty($modName)) $modName = self::getName();
        $modInfo = self::getFileInfo($modName, $type);
        return xarML($modInfo['displayname']);
    }

    /**
     * Get the displayable description for modName
     *
     * The displayable description is sensible to user language.
     *
     * 
     * @param modName string registered name of module
     * @return string the displayable description
     */
    static function getDisplayDescription($modName = NULL, $type = 'module')
    {
        //xarLogMessage("xarMod::getDisplayDescription ". $modName ." / " . $type);
        if (empty($modName)) $modName = self::getName();

        $modInfo = self::getFileInfo($modName, $type);
        return xarML($modInfo['displaydescription']);
    }

    /**
     * Temporary helper function during regid->systemid migration
     *
     * @todo once the migration is done, migrate this out.
     */
    private static function getIds($modName, $type = 'module')
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        // For themes, kinda weird
        $modBaseInfo = self::getBaseInfo($modName,$type);
        if (!isset($modBaseInfo)) return; // throw back
        return array('systemid' => $modBaseInfo['systemid'], 'regid' => $modBaseInfo['regid']);
    }

    /**
     * Get module registry ID by name
     *
     * 
     * @param modName string The name of the module
     * @param type determines theme or module
     * @return string The module registry ID.
     */
    static function getRegId($modName, $type = 'module')
    {
        $ids = self::getIds($modName);
        return !is_null($ids['regid']) ? (int)$ids['regid'] : null;
    }

    /**
     * Get module system ID by name
     *
     * 
     * @param modName string The name of the module
     * @param type determines theme or module
     * @return string The module registry ID.
     */
    static function getId($modName)
    {
        $ids = self::getIds($modName);
        return $ids['systemid'];
    }

    /**
     * Get the module's current state
     *
     * 
     * @param  integer the module's registered id
     * @param type determines theme or module
     * @return mixed the module's current state
     * @throws DATABASE_ERROR, MODULE_NOT_EXIST
     * @todo implement the xarMod__setState reciproke
     * @todo We dont need this, used nowhere
     */
    static function getState($modRegId, $type = 'module')
    {
        $tmp = self::getInfo($modRegId, $type);
        return (int)$tmp['state'];
    }

    /**
     * Check if a module is installed and its state is XARMOD_STATE_ACTIVE
     *
     * 
     * @static modAvailableCache array
     * @param modName string registered name of module
     * @param type determines theme or module
     * @return mixed true if the module is available
     * @throws DATABASE_ERROR, BAD_PARAM
     */
    static function isAvailable($modName, $type = 'module')
    {
        //xarLogMessage("xarMod::isAvailable: begin $type:$modName");

        // FIXME: there is no point to the cache here, since
        // xarMod::getBaseInfo() caches module details anyway.
        static $modAvailableCache = array();

        if (empty($modName)) throw new EmptyParameterException('modName');

        // Get the real module details.
        // The module details will be cached anyway.
        $modBaseInfo = self::getBaseInfo($modName, $type);

        // Return false if the result wasn't set
        if (!isset($modBaseInfo)) return false; // throw back

        if (!empty($GLOBALS['xarMod_noCacheState']) || !isset($modAvailableCache[$modBaseInfo['name']])) {
            // We should be ok now, return the state of the module
            $modState = $modBaseInfo['state'];
            $modAvailableCache[$modBaseInfo['name']] = false;

            if ($modState == XARMOD_STATE_ACTIVE) {
                $modAvailableCache[$modBaseInfo['name']] = true;
            }
        }
        //xarLogMessage("xarMod::isAvailable: end $type:$modName");
        return $modAvailableCache[$modBaseInfo['name']];
    }

    /**
     * Get information on module
     *
     * 
     * @param modRegId string module id
     * @param type determines theme or module
     * @return array of module information
     * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
     */
    static function getInfo($modRegId, $type = 'module')
    {
        if (empty($modRegId)) throw new EmptyParameterException('modRegid');

        switch($type) {
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
        // Log it when it doesnt come from the cache
        xarLogMessage("xarMod::getInfo ". $modRegId ." / " . $type);

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        switch($type) {
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
                             state
                       FROM  $the_table WHERE regid = ?";
            break;
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($modRegId),ResultSet::FETCHMODE_NUM);

        if (!$result->next()) {
            $result->close();
            throw new IDNotFoundException($modRegId);
        }

        switch($type) {
        case 'module':
        default:
            list($modInfo['systemid'],
                 $modInfo['name'],
                 $modInfo['directory'],
                 $modInfo['version'],
                 $modInfo['admincapable'],
                 $modInfo['usercapable'],
                 $modInfo['state']) = $result->getRow();
            break;
        case 'theme':
            list($modInfo['systemid'],
                 $modInfo['name'],
                 $modInfo['directory'],
                 $modInfo['version'],
                 $modInfo['state']) = $result->getRow();
            break;
        }
        $result->Close();
        unset($result);

        $modInfo['regid'] = (int) $modRegId;
        $modInfo['displayname'] = self::getDisplayName($modInfo['name'], $type);
        $modInfo['displaydescription'] = self::getDisplayDescription($modInfo['name'], $type);
        $modInfo['systemid'] = (int)$modInfo['systemid'];
        $modInfo['state'] = (int)$modInfo['state'];

        // Shortcut for os prepared directory
        $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

        switch($type) {
        case 'module':
        default:
            if (!isset($modInfo['state'])) $modInfo['state'] = XARMOD_STATE_MISSING_FROM_UNINITIALISED; //return; // throw back
            $modFileInfo = self::getFileInfo($modInfo['osdirectory']);
            break;
        case 'theme':
            if (!isset($modInfo['state'])) {
                $modInfo['state']= XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
            }
            $modFileInfo = self::getFileInfo($modInfo['osdirectory'], $type = 'theme');
            break;
        }

        if (!isset($modFileInfo)) {
            // We couldn't get file info, fill in unknowns.
            // The exception for this is logged in getFileInfo
            $unknown = xarML('Unknown');
            $modFileInfo['class'] = $unknown;
            $modFileInfo['description'] = xarML('This module is not installed properly. Not all info could be retrieved');
            $modFileInfo['category'] = $unknown;
            $modFileInfo['displayname'] = $unknown;
            $modFileInfo['displaydescription'] = $unknown;
            $modFileInfo['author'] = $unknown;
            $modFileInfo['contact'] = $unknown;
            $modFileInfo['admin'] = $unknown;
            $modFileInfo['user'] = $unknown;
            $modFileInfo['dependency'] = array();
            $modFileInfo['extensions'] = array();

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

        switch($type) {
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
     * 
     * @param modName string the module's name
     * @param type determines theme or module
     * @return mixed an array of base module info on success
     * @throws EmptyParameterException, BadParameterException
     */
    static function getBaseInfo($modName, $type = 'module')
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        if ($type != 'module' && $type != 'theme') {
            throw new BadParameterException($type,'The value of the "type" parameter must be "module" or "theme", it was "#(1)"');
        }

        // The GLOBALS['xarMod_noCacheState'] flag tells Xaraya *not*
        // to cache module (+state) where this would lead to problems
        // like in the installer for example.
        if ($type == 'module') {
            $cacheCollection = 'Mod.BaseInfos';
            $checkNoState = 'xarMod_noCacheState';
        } else {
            $cacheCollection = 'Theme.BaseInfos';
            $checkNoState = 'xarTheme_noCacheState';
        }

        if (empty($GLOBALS[$checkNoState]) && xarCoreCache::isCached($cacheCollection, $modName)) {
            return xarCoreCache::getCached($cacheCollection, $modName);
        }
        // Log it when it doesnt come from the cache
        xarLogMessage("xarMod::getBaseInfo ". $modName ." / ". $type);

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        // theme+s or module+s
        $table = $tables[$type.'s'];

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
        $bindvars = array($modName, $modName);
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);

        if (!$result->next()) {
            $result->Close();
            return;
        }

        $modBaseInfo = array();
        if ($type == 'theme') {
            list($regid,  $directory, $systemid, $version, $state, $name, $configuration) = $result->getRow();
        } else {
            list($regid,  $directory, $systemid, $version, $state, $name) = $result->getRow();
        }
        $result->Close();

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
        $modBaseInfo['osdirectory'] = xarVarPrepForOS($directory);
        if ($type == 'theme') {
            try {
                $modBaseInfo['configuration'] = unserialize($configuration);
            } catch (Exception $e) {
                $modBaseInfo['configuration'] = array();
            }
        }

        // This needed?
        if (empty($modBaseInfo['state'])) {
            $modBaseInfo['state'] = XARMOD_STATE_UNINITIALISED;
        }
        xarCoreCache::setCached($cacheCollection, $name, $modBaseInfo);

        return $modBaseInfo;
    }

    /**
     * Get info from xarversion.php for module specified by modOsDir
     *
     * 
     * @param modOSdir the module's directory
     * @param type determines theme or module
     * @return array an array of module file information
     * @throws MODULE_FILE_NOT_EXIST
     * @todo <marco> #1 FIXME: admin or admin capable?
     */
    static function getFileInfo($modOsDir, $type = 'module')
    {
        if (empty($modOsDir)) throw new EmptyParameterException('modOsDir');

        if (empty($GLOBALS['xarMod_noCacheState']) && xarCoreCache::isCached('Mod.getFileInfos', $modOsDir ." / " . $type)) {
            return xarCoreCache::getCached('Mod.getFileInfos', $modOsDir ." / " . $type);
        }
        // Log it when it didnt came from cache
        xarLogMessage("xarMod::getFileInfo ". $modOsDir ." / " . $type);


        // TODO redo legacy support via type.
        switch($type) {
        case 'module':
            // Spliffster, additional mod info from modules/$modDir/xarversion.php
            $fileName = sys::code() . 'modules/' . $modOsDir . '/xarversion.php';
            $part = 'xarversion';
            // If the locale is already present, it means we can make the translations available
            if(!empty($GLOBALS['xarMLS_currentLocale']))
                xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modOsDir, 'modules:', 'version');
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
            $fileName = xarConfigVars::get(null,'Site.BL.ThemesDirectory') . '/' . $modOsDir . '/xartheme.php';
            $part = 'xartheme';
            break;
        default:
            throw new BadParameterException('module/theme type');
        }

        if (!file_exists($fileName)) {
            // Don't raise an exception, it is too harsh, but log it tho (bug 295)
            xarLogMessage("xarMod::getFileInfo: Could not find xarversion.php, skipping $modOsDir");
            // throw new FileNotFoundException($fileName);
            return;
        }
        // We can NOT use sys::import here, since the xarversion/xartheme files contain variables only
        // If they were loaded earlier, sys::import does nothing (as it should)
        // since inclusion of variables can be done multiple times (they just get overwritten)
        // the include is safe. Ergo: leave this in place.
        include $fileName;

        if (!isset($themeinfo))  $themeinfo = array();
        if (!isset($modversion)) $modversion = array();

        $version = array_merge($themeinfo, $modversion);

        // name and id are required, assert them, otherwise the module is invalid
        assert('isset($version["name"]) && isset($version["id"]); /* Both name and id need to be present in xarversion.php */');
        $FileInfo['name']           = $version['name'];
        $FileInfo['regid']          = (int) $version['id'];
        $FileInfo['displayname']    = isset($version['displayname'])    ? $version['displayname'] : $version['name'];
        $FileInfo['description']    = isset($version['description'])    ? $version['description'] : false;
        $FileInfo['displaydescription'] = isset($version['displaydescription']) ? $version['displaydescription'] : $FileInfo['description'];
        $FileInfo['admin']          = isset($version['admin'])          ? (bool) $version['admin'] : false;
        $FileInfo['admin_capable']  = isset($version['admin'])          ? (bool) $version['admin'] : false;
        $FileInfo['user']           = isset($version['user'])           ? (bool) $version['user'] : false;
        $FileInfo['user_capable']   = isset($version['user'])           ? (bool) $version['user'] : false;
        $FileInfo['securityschema'] = isset($version['securityschema']) ? $version['securityschema'] : false;
        $FileInfo['class']          = isset($version['class'])          ? $version['class'] : false;
        $FileInfo['category']       = isset($version['category'])       ? $version['category'] : false;
        $FileInfo['locale']         = isset($version['locale'])         ? $version['locale'] : 'en_US.iso-8859-1';
        $FileInfo['author']         = isset($version['author'])         ? $version['author'] : false;
        $FileInfo['contact']        = isset($version['contact'])        ? $version['contact'] : false;
        $FileInfo['dependency']     = isset($version['dependency'])     ? $version['dependency'] : array();
        $FileInfo['dependencyinfo'] = isset($version['dependencyinfo']) ? $version['dependencyinfo'] : array();
        $FileInfo['propertyinfo']   = isset($version['propertyinfo'])   ? $version['propertyinfo'] : array();
        $FileInfo['extensions']     = isset($version['extensions'])     ? $version['extensions'] : array();
        $FileInfo['directory']      = isset($version['directory'])      ? $version['directory'] : false;
        $FileInfo['homepage']       = isset($version['homepage'])       ? $version['homepage'] : false;
        $FileInfo['email']          = isset($version['email'])          ? $version['email'] : false;
        $FileInfo['contact_info']   = isset($version['contact_info'])   ? $version['contact_info'] : false;
        $FileInfo['publish_date']   = isset($version['publish_date'])   ? $version['publish_date'] : false;
        $FileInfo['license']        = isset($version['license'])        ? $version['license'] : false;
        $FileInfo['version']        = isset($version['version'])        ? $version['version'] : false;
        // Check that 'xar_version' key exists before assigning
        if (!$FileInfo['version'] && isset($version['xar_version'])) {
            $FileInfo['version'] = $version['xar_version'];
        }
        $FileInfo['bl_version']     = isset($version['bl_version'])     ? $version['bl_version'] : false;

        xarCoreCache::setCached('Mod.getFileInfos', $modOsDir ." / " . $type, $FileInfo);
        return $FileInfo;
    }

    /**
     * Load database definition for a module
     *
     * 
     * @param modName string name of module to load database definition for
     * @param modOsDir string directory that module is in
     * @return mixed true on success
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
     *
     * @todo make this private again
     */
    static function loadDbInfo($modName, $modDir = NULL, $type = 'module')
    {
        static $loadedDbInfoCache = array();

        if($type == 'theme') return true; // sigh.

        if (empty($modName)) throw new EmptyParameterException('modName');

        // Check to ensure we aren't doing this twice
        if (isset($loadedDbInfoCache[$modName])) return true;

        // Get the directory if we don't already have it
        if (empty($modDir)) {
            $modBaseInfo = self::getBaseInfo($modName,$type);
            if (!isset($modBaseInfo)) return; // throw back
            $modDir = xarVarPrepForOS($modBaseInfo['directory']);
        } else {
            $modDir = xarVarPrepForOS($modDir);
        }

        // For base and modules, which don't have a xartables - CHECKME: why not again ?
        if (!file_exists(sys::code() . 'modules/' . $modDir . '/xartables.php')) {
            // set anyway, so we don't try over and over
            $loadedDbInfoCache[$modName] = false;
            return false;
        }

        // Load the database definition if required
        try {
            sys::import('modules.'.$modDir.'.xartables');
        } catch (Exception $e) {
            // set anyway, so we don't try over and over
            $loadedDbInfoCache[$modName] = false;
            return false;
        }

        $tablefunc = $modName . '_' . 'xartables';
        if (function_exists($tablefunc)) xarDB::importTables($tablefunc());

        $loadedDbInfoCache[$modName] = true;
        return true;
    }

    /**
     * Call a module GUI function.
     *
     * 
     * @param modName string registered name of module
     * @param modType string type of function to run
     * @param funcName string specific function to run
     * @param args array
     * @return mixed The output of the function, or raise an exception
     * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
     */
    static function guiFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        // Get a cache key for this module function if it's suitable for module caching
        $cacheKey = xarCache::getModuleKey($modName, $modType, $funcName, $args);

        // Check if the module function is cached
        if (!empty($cacheKey) && xarModuleCache::isCached($cacheKey)) {
            // Return the cached module function output
            return xarModuleCache::getCached($cacheKey);
        }
        $tplData = self::callFunc($modName,$modType,$funcName,$args);
        // If we have a string of data, we assume someone else did xarTpl* for us
        if (!is_array($tplData)) {
            // Set the output of the module function in cache
            if (!empty($cacheKey)) {
                xarModuleCache::setCached($cacheKey, $tplData);
            }
            return $tplData;
        }

        // See if we have a special template to apply
        $templateName = NULL;
        if (isset($tplData['_bl_template'])) $templateName = $tplData['_bl_template'];

        // Create the output.
        $tplOutput = xarTpl::module($modName, $modType, $funcName, $tplData, $templateName);

        // Set the output of the module function in cache
        if (!empty($cacheKey)) {
            xarModuleCache::setCached($cacheKey, $tplOutput);
        }

        return $tplOutput;
    }

    /**
     * Call a module API function.
     *
     * Using the modules name, type, func, and optional arguments
     * builds a function name by joining them together
     * and using the optional arguments as parameters
     * like so:
     * Ex: modName_modTypeapi_modFunc($args);
     *
     * 
     * @param modName string registered name of module
     * @param modType string type of function to run
     * @param funcName string specific function to run
     * @param args array arguments to pass to the function
     * @return mixed The output of the function, or false on failure
     * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
     */
    static function apiFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
    {
        if (empty($modName)) throw new EmptyParameterException('modName');
        return self::callfunc($modName, $modType, $funcName, $args,'api');
    }

    /**
     * Work horse method for the lazy calling of module functions
     *
     * 
     */
    private static function callFunc($modName,$modType,$funcName,$args,$funcType = '')
    {
        assert('($funcType == "api" or $funcType==""); /* Wrong funcType argument in private callFunc method */');

        // Build function name
        $modFunc = "{$modName}_{$modType}{$funcType}_{$funcName}";
        if (empty($modName) || empty($funcName)) {
            // This is not a valid function syntax - CHECKME: also for api functions ?
            if ($funcType == "api") throw new FunctionNotFoundException($modFunc);
            else return xarController::$response->NotFound();
        }

        // good thing this information is cached :)
        $modBaseInfo = self::getBaseInfo($modName);
        if (!isset($modBaseInfo)) {
            // This is not a valid module - CHECKME: also for api functions ?
            if ($funcType == "api") throw new FunctionNotFoundException($modFunc);
            else return xarController::$response->NotFound();
        }

        // Call function
        $found = true;
        $isLoaded = true;
        $msg = '';
        if (!function_exists($modFunc)) {
            // attempt to load the module's api
            if ($funcType == 'api') {
                xarMod::apiLoad($modName, $modType);
            } else {
                try {
                    xarMod::load($modName,$modType);
                } catch (Exception $e) {
                    return xarController::$response->NotFound();
                }
            }
            // let's check for that function again to be sure
            if (!function_exists($modFunc)) {
                // Q: who are we kidding with this? osdirectory == modName always, no?
                $funcFile = sys::code() . 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.$funcType.'/'.strtolower($funcName).'.php';
                if (!file_exists($funcFile)) {
                    // Valid syntax, but the function doesn't exist
                    if ($funcType == "api") throw new FunctionNotFoundException($modFunc);
                    else return xarController::$response->NotFound();
                } else {
                    ob_start();
                    sys::import('modules.'.$modName.'.xar'.$modType.$funcType.'.'.strtolower($funcName));
                    $error_msg = strip_tags(ob_get_contents());
                    ob_end_clean();

                    if (empty($r) || !$r) {
                        $msg = "Could not load function file: [#(1)].\n\n Error Caught:\n #(2)";
                        $params = array($funcFile, $error_msg);
                        $isLoaded = false;
                    }
                    if (!function_exists($modFunc)) $found = false;
                }
            }

            if ($found) {
                // Load the translations file, only if we have loaded the API function for the first time here.
                if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:'.$modType.$funcType, $funcName) === NULL) {return;}
            }
        }

        if (!$found) return xarController::$response->NotFound();

        $funcResult = $modFunc($args);
        return $funcResult;
    }

    /**
     * Load the modType of module identified by modName.
     *
     * 
     * @param modName string - name of module to load
     * @param modType string - type of functions to load
     * @return mixed
     * @throws XAR_SYSTEM_EXCEPTION
     */
    static function load($modName, $modType = 'user')
    {
        return self::privateLoad($modName, $modType);
    }

    /**
     * Load the modType API for module identified by modName.
     *
     * 
     * @param modName string registered name of the module
     * @param modType string type of functions to load
     * @return mixed true on success
     * @throws XAR_SYSTEM_EXCEPTION
     */
    static function apiLoad($modName, $modType = 'user')
    {
        return self::privateLoad($modName, $modType.'api', XARMOD_LOAD_ANYSTATE);
    }

    /**
     * Load the modType of module identified by modName.
     *
     * 
     * @param modName string - name of module to load
     * @param modType string - type of functions to load
     * @param flags number - flags to modify function behaviour
     * @return mixed
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
     */
    static private function privateLoad($modName, $modType, $flags = 0)
    {
        static $loadedModuleCache = array();
        if (empty($modName)) throw new EmptyParameterException('modName');

        // Make sure we access the cache with lower case key, return true when we already loaded
        $cacheKey = strtolower($modName.$modType);
        if (isset($loadedModuleCache[$cacheKey])) return true;

        // Log it when it doesnt come from the cache
        xarLogMessage("xarMod::load: loading $modName:$modType");

        $modBaseInfo = self::getBaseInfo($modName);
        // Not a valid module - throw exception
        if (!isset($modBaseInfo)) throw new ModuleNotFoundException($modName);

        // Not a valid module state - throw exception
        if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE && !($flags & XARMOD_LOAD_ANYSTATE) ) {
            throw new ModuleNotActiveException($modName);
        }
        
        // Not the correct version - throw exception unless we are upgrading
        if (!self::checkVersion($modName) && !xarVarGetCached('Upgrade', 'upgrading')) {
            die('The core module "' . $modName . '" does not have the correct version. Please run the upgrade routine by clicking <a href="upgrade.php">here</a>');
        }
        
        // Load the module files
        $modDir = $modBaseInfo['directory'];
        $fileName = sys::code() . 'modules/'.$modDir.'/xar'.$modType.'.php';

        // Removed the exception.  Causing some wierd results with modules without an api.
        // <nuncanada> But now we wont know if something was loaded or not!
        // <nuncanada> We need some way to find it out.
        // Assume failure
        if (file_exists($fileName)) {
            sys::import('modules.'.$modDir.'.xar'.$modType);
            $loadedModuleCache[$cacheKey] = true;
        } elseif (is_dir(sys::code() . 'modules/'.$modDir.'/xar'.$modType)) {
            // this is OK too - do nothing
            $loadedModuleCache[$cacheKey] = true;
        } else {
            // this is (not really) OK too - do nothing
            $loadedModuleCache[$cacheKey] = false;
        }

        // Load the module translations files (common functions, uncut functions etc.)
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:', $modType) === NULL) return;

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
     * Check the version of this moduleagainst the core version
     *
     * @return boolean
     */
    public static function checkVersion($modName)
    {
        $modInfo = self::getInfo(self::getRegId($modName));
        if ((strpos($modInfo['class'], 'Core') !== false)) {
            return $modInfo['version'] == XARCORE_VERSION_NUM;
        } else {
            // Add check for non core modules here
            return true;
        }
    }
    
    /**
     * Check if a particular module function exists, or default back to 'dynamicdata'
     *
     * @return string tplmodule or 'dynamicdata'
     */
    static function checkModuleFunction($tplmodule = 'dynamicdata', $type = 'user', $func = 'display', $defaultmodule = 'dynamicdata')
    {
        static $tplmodule_cache = array();

        $key = "$tplmodule:$type:$func";
        if (!isset($tplmodule_cache[$key])) {
            $file = sys::code() . 'modules/' . $tplmodule . '/xar' . $type . '/' . $func . '.php';
            if (file_exists($file)) {
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
     * 
     * @param moduleName string the module we want to check access for
     * @param action string the action we want to take on this module (view/admin) // CHECKME: any others we really use on module level ?
     * @param roleid mixed override the current user or null
     * @return boolean true if access
     */
    static function checkAccess($moduleName, $action, $roleid = null)
    {
        // TODO: get module variable with access config: groups, masks, levels or whatever

        // TODO: check for access e.g. by group

        // Fall back on mask-less security check with access levels corresponding to action
        sys::import('modules.privileges.class.securitylevel');

        // default actions supported on modules
        switch($action)
        {
            case 'admin':
                $seclevel = SecurityLevel::ADMIN;
                break;

        // CHECKME: any others we really use on module level (instead of object/item/block/... level) ?

            case 'view':
                $seclevel = SecurityLevel::OVERVIEW;
                break;

            default:
                throw new BadParameterException('action', "Supported actions on module level are 'view' and 'admin'");
                break;
        }

        if (!empty($roleid)) {
            $role = xarRoles::get($roleid);
            $rolename = $role->getName();
            return xarSecurity::check('',0,'All','All',$moduleName,$rolename,0,$seclevel);
        } else {
            return xarSecurity::check('',0,'All','All',$moduleName,'',0,$seclevel);
        }
    }
}


/**
 * Wrapper functions to support Xaraya 1 API for module aliases
 *
 */
function xarModGetAlias($alias) { return xarModAlias::resolve($alias);}
function xarModSetAlias($alias, $modName) { return xarModAlias::set($alias,$modName);}
function xarModDelAlias($alias, $modName) { return xarModAlias::delete($alias,$modName);}

/**
 * Interface declaration for module aliases
 *
 */
interface IxarModAlias
{
    static function resolve($alias);
    static function set    ($alias,$modName);
    static function delete ($alias,$modName);
}

/**
 * Class to model interface to module aliases
 *
 * @todo evaluate dependency consequences
 * @todo evaluate usage in modules, it's not very common, as in, perhaps worth to scrap and bolt onto a request mapper
 */
class xarModAlias extends Object implements IxarModAlias
{
    /**
     * Resolve an alias for a module
     */
    static function resolve($alias)
    {
        $aliasesMap = xarConfigVars::get(null,'System.ModuleAliases');
        return (!empty($aliasesMap[$alias])) ? $aliasesMap[$alias] : $alias;
    }

    /**
     * Set an alias for a module
     */
    static function set($alias,$modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) return;
        $args = array('modName' => $modName, 'aliasModName' => $alias);
        return xarMod::apiFunc('modules', 'admin', 'add_module_alias', $args);
    }

    /**
     * Delete an alias for a module
     */
    static function delete($alias, $modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) return;
        $args = array('modName' => $modName, 'aliasModName' => $alias);
        return xarMod::apiFunc('modules', 'admin', 'delete_module_alias', $args);
    }
}
?>
