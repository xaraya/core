<?php
/**
 * The Core
 *
 * @package core
 * @copyright (C) 2002-2006 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo dependencies and runlevels!
**/

/**
 * Core version informations
 *
 * should be upgraded on each release for
 * better control on config settings
 *
**/

// Handy if we're running from a mt working copy, prolly comment out on distributing
$rev = 'unknown';
if(file_exists('../_MTN/revision'))
{
    $t= file('../_MTN/revision');
    if (isset($t[4]))
        $rev = str_replace(array('old_revision [',']'),'',$t[4]);
}
define('XARCORE_VERSION_REV', $rev);

/*
 * System dependencies for (optional) systems
 * FIXME: This diagram isn't correct (or at least not detailed enough)
 * -------------------------------------------------------
 * | Name           | Depends on                | Define |
 * -------------------------------------------------------
 * | EXCEPTIONS     | nothing (really ?)        |        |
 * | LOG            | nothing                   |        |
 * | SYSTEMVARS     | nothing                   |        |
 * | DATABASE       | SYSTEMVARS                |    1   |
 * | EVENTS         | nothing ?                 |        |
 * | CONFIGURATION  | DATABASE                  |    8   |
 * | LEGACY         | CONFIGURATION             |        |
 * | SERVER         | CONFIGURATION (?)         |        |
 * | MLS            | CONFIGURATION             |        |
 * | SESSION        | CONFIGURATION (?), SERVER |    2   |
 * | BLOCKS         | CONFIGURATION             |   16   |
 * | MODULES        | CONFIGURATION             |   32   |
 * | TEMPLATE       | MODULES, MLS (?)          |   64   |
 * | USER           | SESSION, MODULES          |    4   |
 * -------------------------------------------------------
 *
 * TODO: update dependencies and order
 *
 *   DATABASE           (00000001)
 *   |
 *   |- CONFIGURATION   (00001001)
 *      |
 *      |- SESSION      (00001011)
 *      |
 *      |- BLOCKS       (00011001)
 *      |
 *      |- MODULES      (00101001)
 *         |
 *         |- USER      (00101111)
 *
 *   ALL                (01111111)
 */

/**#@+
 * Optional systems defines that can be used as parameter for xarCoreInit
 * System dependancies are yet present in the define, so you don't
 * have to care of what for example the SESSION system depends on, if you
 * need it you just pass XARCORE_SYSTEM_SESSION to xarCoreInit and its
 * dependancies will be automatically resolved
 *
 * @access public
 * @todo   bring these under a class as constant
**/
define('XARCORE_SYSTEM_NONE'         , 0);
define('XARCORE_SYSTEM_DATABASE'     , 1);
define('XARCORE_SYSTEM_CONFIGURATION', 8 | XARCORE_SYSTEM_DATABASE);
define('XARCORE_SYSTEM_SESSION'      , 2 | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_BLOCKS'       , 16 | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_MODULES'      , 32 | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_USER'         , 4 | XARCORE_SYSTEM_SESSION | XARCORE_SYSTEM_MODULES);
define('XARCORE_SYSTEM_ALL'          , 127); // bit OR of all optional systems (includes templates now)
/**#@-*/

/**#@+
 * Bit defines to keep track of the loading based on the defines which
 * are passed in as arguments
 *
 * @access private
 * @todo we should probably get rid of these
**/
define('XARCORE_BIT_DATABASE'     ,  1);
define('XARCORE_BIT_SESSION'      ,  2);
define('XARCORE_BIT_USER'         ,  4);
define('XARCORE_BIT_CONFIGURATION',  8);
define('XARCORE_BIT_BLOCKS'       , 16);
define('XARCORE_BIT_MODULES'      , 32);
define('XARCORE_BIT_TEMPLATE'     , 64);
/**#@-*/

/**#@+
 * Debug flags
 *
 * @access private
 * @todo   encapsulate in class
**/
define('XARDBG_ACTIVE'           , 1);
define('XARDBG_SQL'              , 2);
define('XARDBG_EXCEPTIONS'       , 4);
define('XARDBG_SHOW_PARAMS_IN_BT', 8);
define('XARDBG_INACTIVE'         ,16);
/**#@-*/

/**#@+
 * Miscelaneous defines
 *
 * @access public
 * @todo encapsulate in class
**/
define('XARCORE_CACHEDIR'     , '/cache');
define('XARCORE_DB_CACHEDIR'  , '/cache/database');
define('XARCORE_RSS_CACHEDIR' , '/cache/rss');
define('XARCORE_TPL_CACHEDIR' , '/cache/templates');
/**#@-*/

/*
 * Load the Xaraya pre core early in case the entry point didn't do it (it should)
 *
 */
if(!class_exists('sys'))
{
    // @todo: this aint right, it's not here, but one level up.
    include (dirname(__FILE__).'/bootstrap.php');
}
// Before we do anything make sure we can except out of code in a predictable matter
sys::import('xaraya.exceptions');

/**
 * Initializes the core engine
 *
 * @access public
 * @param integer whatToLoad What optional systems to load.
 * @return bool true
 * @todo <johnny> fix up sitetable prefix when we have a place to store it
**/
function xarCoreInit($whatToLoad = XARCORE_SYSTEM_ALL)
{
    static $current_load_level = XARCORE_SYSTEM_NONE;
    static $first_load = true;

    $new_load_level = $whatToLoad;

    // Make sure it only loads the current load level (or less than the current load level) once.
    if ($whatToLoad <= $current_load_level) {
        if (!$first_load) return true; // Does this ever happen? If so, we might consider an assert
        $first_load = false;
    } else {
        // if we are loading a load level higher than the
        // current one, make sure to XOR out everything
        // that we've already loaded
        $whatToLoad ^= $current_load_level;
    }

    /*
     * At this point we should be able to catch all low level errors, so we can start the debugger
     *
     * FLAGS:
     *
     * XARDBG_INACTIVE          disable  the debugger
     * XARDBG_ACTIVE            enable   the debugger
     * XARDBG_EXCEPTIONS        debug exceptions
     * XARDBG_SQL               debug SQL statements
     * XARDBG_SHOW_PARAMS_IN_BT show parameters in the backtrace
     *
     * Flags can be OR-ed together
     */
// CHECKME: make this configurable too !?
    xarCoreActivateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT );
//    xarCoreActivateDebugger(XARDBG_INACTIVE);

    /*
     * If there happens something we want to be able to log it
     *
     */
    $systemArgs = array();
    sys::import('xaraya.log');
    xarLog_init($systemArgs);

    /*
     * Start Database Connection Handling System
     *
     * Most of the stuff, except for logging, exception and system related things,
     * we want to do in the database, so initialize that as early as possible.
     * It think this is the earliest we can do
     *
     */
    if ($whatToLoad & XARCORE_SYSTEM_DATABASE) { // yeah right, as if this is optional
        sys::import('xaraya.variables.system');

        // Decode encoded DB parameters
        // These need to be there
        $userName = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
        $password = xarSystemVars::get(sys::CONFIG, 'DB.Password');
        $persistent = null;
        try {
            $persistent = xarSystemVars::get(sys::CONFIG, 'DB.Persistent');
        } catch(VariableNotFoundException $e) {
            $persistent = null;
        }
        try {
            if (xarSystemVars::get(sys::CONFIG, 'DB.Encoded') == '1') {
                $userName = base64_decode($userName);
                $password  = base64_decode($password);
            }
        } catch(VariableNotFoundException $e) {
            // doesnt matter, we assume not encoded
        }

        // Optionals dealt with, do the rest inline
        $systemArgs = array('userName' => $userName,
                            'password' => $password,
                            'databaseHost'    => xarSystemVars::get(sys::CONFIG, 'DB.Host'),
                            'databaseType'    => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                            'databaseName'    => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                            'databaseCharset' => xarSystemVars::get(sys::CONFIG, 'DB.Charset'),
                            'persistent'      => $persistent,
                            'prefix'          => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'));

        sys::import('xaraya.database');

        // Connect to database
        xarDB_init($systemArgs);
        $whatToLoad ^= XARCORE_BIT_DATABASE;
    }

    /*
     * Start Event Messaging System
     *
     * The event messaging system can be initialized only after the db, but should
     * be as early as possible in place. This system is for *core* events
     *
     */
    sys::import('xaraya.events');

/* CHECKME: initialize autoload based on config vars, or based on modules, or earlier ?
    sys::import('xaraya.autoload');
    xarAutoload::initialize();

// Testing of autoload + second-level cache storage - please do not use on live sites
    sys::import('xaraya.caching.storage');
    $cache = xarCache_Storage::getCacheStorage(array('storage' => 'xcache'));
    xarCore::setCacheStorage($cache);
    // For bulk load, we might have to do this after loading the modules, otherwise
    // unserialize + autoload might trigger a function that complains about xarMod:: etc.
    //xarCore::setCacheStorage($cache,0,1);
*/

    /*
     * Start Configuration System
     *
     * Ok, we can  except, we can log our actions, we can access the db and we can
     * send events out of the core. It's time we start the configuration system, so we
     * can start configuring the framework
     *
     */
    if ($whatToLoad & XARCORE_SYSTEM_CONFIGURATION) {
        // Start Variables utilities
        sys::import('xaraya.variables');
        xarVar_init($systemArgs);
        $whatToLoad ^= XARCORE_BIT_CONFIGURATION;

    // we're about done here - everything else requires configuration, at least to initialize them !?
    } else {
        // Make the current load level == the new load level
        $current_load_level = $new_load_level;
        return true;
    }

    /**
     * Legacy systems
     *
     * Before anything fancy is loaded, let's start the legacy systems
     *
     */
    if (xarConfigVars::get(null, 'Site.Core.LoadLegacy') == true) {
        sys::import('xaraya.legacy');
    }

    /*
     * At this point we haven't made any assumptions about architecture
     * except that we use a database as storage container.
     *
     */

    /*
     * Bring HTTP Protocol Server/Request/Response utilities into the story
     *
     */
    sys::import('xaraya.server');
    $systemArgs = array('enableShortURLsSupport' => xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport'),
                        'generateXMLURLs' => true);
    xarServer::init($systemArgs);
    xarRequest::init($systemArgs);
    xarResponse::init($systemArgs);

    /*
     * Bring Multi Language System online
     *
     */
    sys::import('xaraya.mls');
    // FIXME: Site.MLS.MLSMode is NULL during install
    $systemArgs = array('MLSMode'             => xarConfigVars::get(null, 'Site.MLS.MLSMode'),
//                      'translationsBackend' => xarConfigVars::get(null, 'Site.MLS.TranslationsBackend'),
                        'translationsBackend' => 'xml2php',
                        'defaultLocale'       => xarConfigVars::get(null, 'Site.MLS.DefaultLocale'),
                        'allowedLocales'      => xarConfigVars::get(null, 'Site.MLS.AllowedLocales'),
                        'defaultTimeZone'     => xarConfigVars::get(null, 'Site.Core.TimeZone'),
                        'defaultTimeOffset'   => xarConfigVars::get(null, 'Site.MLS.DefaultTimeOffset'),
                        );
    xarMLS_init($systemArgs);



    /*
     * We deal with users through the sessions subsystem
     *
     */
    // @todo Assuming a fixed 2 here needs to be reviewed, core is a too low level system to assume this.
    $anonid = xarConfigVars::get(null, 'Site.User.AnonymousUID',2);
    define('_XAR_ID_UNREGISTERED', $anonid);

    if ($whatToLoad & XARCORE_SYSTEM_SESSION)
    {
        sys::import('xaraya.sessions');

        $systemArgs = array(
            'securityLevel'     => xarConfigVars::get(null, 'Site.Session.SecurityLevel'),
            'duration'          => xarConfigVars::get(null, 'Site.Session.Duration'),
            'inactivityTimeout' => xarConfigVars::get(null, 'Site.Session.InactivityTimeout'),
            'cookieName'        => xarConfigVars::get(null, 'Site.Session.CookieName'),
            'cookiePath'        => xarConfigVars::get(null, 'Site.Session.CookiePath'),
            'cookieDomain'      => xarConfigVars::get(null, 'Site.Session.CookieDomain'),
            'refererCheck'      => xarConfigVars::get(null, 'Site.Session.RefererCheck'));
        xarSession_init($systemArgs);

        $whatToLoad ^= XARCORE_BIT_SESSION;
    }

    /*
     * Block subsystem
     *
     */
    // FIXME: This is wrong, should be part of templating
    //        it's a legacy thought, we don't need it anymore

    if ($whatToLoad & XARCORE_SYSTEM_BLOCKS)
    {
        sys::import('xaraya.blocks');

        // Start Blocks Support Sytem
        $systemArgs = array();
        xarBlock_init($systemArgs);
        $whatToLoad ^= XARCORE_BIT_BLOCKS;
    }


    /**
     * Start Modules Subsystem
     *
     * @todo <mrb> why is this optional?
     * @todo <marco> Figure out how to dynamically compute generateXMLURLs argument based on browser request or XHTML site compliance. For now just pass true.
     * @todo <mrb> i thought it was configurable
    **/
    if ($whatToLoad & XARCORE_SYSTEM_MODULES) {
        sys::import('xaraya.modules');
        $systemArgs = array('enableShortURLsSupport' => xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => true);
        xarMod::init($systemArgs);
        $whatToLoad ^= XARCORE_BIT_MODULES;

    // we're about done here - everything else requires modules !?
    } else {
        // Make the current load level == the new load level
        $current_load_level = $new_load_level;
        return true;
    }

    /*
     * We've got basically all we want, start the interface
     * Start BlockLayout Template Engine
     *
     */
    sys::import('xaraya.templates');

    $systemArgs = array(
        'enableTemplatesCaching' => xarConfigVars::get(null, 'Site.BL.CacheTemplates'),
        'defaultThemeDir'        => xarModVars::get('themes', 'default','default'),
        'generateXMLURLs'        => true
    );

    xarTpl_init($systemArgs);
    $whatToLoad ^= XARCORE_BIT_TEMPLATE;

    /**
     * At last, we can give people access to the system.
     *
     * @todo <marcinmilan> review what pasts of the old user system need to be retained
    **/
    if ($whatToLoad & XARCORE_SYSTEM_USER)
    {
        sys::import('xaraya.users');
        sys::import('xaraya.security');

        // Start User System
        $systemArgs = array('authenticationModules' => xarConfigVars::get(null, 'Site.User.AuthenticationModules'));
        xarUser_init($systemArgs);
        $whatToLoad ^= XARCORE_BIT_USER;
    }

    // Make the current load level == the new load level
    $current_load_level = $new_load_level;
    return true;
}

/**
 * Activates the debugger.
 *
 * @access public
 * @param integer $flags bit mask for the debugger flags
 * @todo  a big part of this should be in the exception (error handling) subsystem.
 * @return void
**/
function xarCoreActivateDebugger($flags)
{
    xarDebug::$flags = $flags;
    if ($flags & XARDBG_INACTIVE) {
        // Turn off error reporting
        error_reporting(0);
        // Turn off assertion evaluation
        assert_options(ASSERT_ACTIVE, 0);
    } elseif ($flags & XARDBG_ACTIVE) {
        // See if config.system.php has info for us on the errorlevel, but dont break if it has not
        try {
            sys::import('xaraya.variables.system');
            $errLevel = xarSystemVars::get(sys::CONFIG, 'Exception.ErrorLevel');
        } catch(Exception $e) {
            $errLevel = E_ALL;
        }

        error_reporting($errLevel);
        // Activate assertions
        assert_options(ASSERT_ACTIVE,    1);    // Activate when debugging
        assert_options(ASSERT_WARNING,   1);    // Issue a php warning
        assert_options(ASSERT_BAIL,      0);    // Stop processing?
        assert_options(ASSERT_QUIET_EVAL,0);    // Quiet evaluation of assert condition?
        xarDebug::$sqlCalls = 0;
        $lmtime = explode(' ', microtime());
        xarDebug::$startTime = $lmtime[1] + $lmtime[0];
    }
}

/**
 * Check if the debugger is active
 *
 * @access public
 * @return bool true if the debugger is active, false otherwise
**/
function xarCoreIsDebuggerActive()
{
    return xarDebug::$flags & XARDBG_ACTIVE;
}

/**
 * Check for specified debugger flag.
 *
 * @access public
 * @param integer flag the debugger flag to check for activity
 * @return bool true if the flag is active, false otherwise
**/
function xarCoreIsDebugFlagSet($flag)
{
    return (xarDebug::$flags & XARDBG_ACTIVE) && (xarDebug::$flags & $flag);
}

/**
 * Checks if a certain function was disabled in php.ini
 *
 *
 * @access public
 * @param string $funcName The function name; case-sensitive
 * @todo this seems out of place here.
**/
function xarFuncIsDisabled($funcName)
{
    static $disabled;

    if (!isset($disabled))
    {
        // Fetch the disabled functions as an array.
        // White space is trimmed here too.
        $functions = preg_split('/[\s,]+/', trim(ini_get('disable_functions')));

        if ($functions[0] != '')
        {
            // Make the function names the keys.
            // Values will be 0, 1, 2 etc.
            $disabled = array_flip($functions);
        } else {
            $disabled = array();
        }
    }

    return (isset($disabled[$funcName]) ? true : false);
}

/**
 * Convenience class for keeping track of debugger operation
 *
 * @package debug
 * @todo this is close to exceptions or logging than core, see also notes earlier
**/
class xarDebug extends Object
{
    public static $flags     = 0; // default off?
    public static $sqlCalls  = 0; // Should be in flags imo
    public static $startTime = 0; // Should not be here at all
}

/**
 * Convenience class for keeping track of core cached stuff
 *
 * @package core
 * @todo this is closer to the caching subsystem than here
 * @todo i dont like the array shuffling
 * @todo separate file
 * @todo this is not xarCore, this is xarCoreCache
**/
class xarCore extends Object
{
    const GENERATION = 2;
    // The actual version information
    const VERSION_NUM = XARCORE_VERSION_REV;
    const VERSION_ID  = 'Jamaica';
    const VERSION_SUB = 'post rabiem risus';

    private static $cacheCollection = array();
    private static $cacheStorage = null;
    private static $isBulkStorage = 0;

    /**
     * Check if a variable value is cached
     *
     * @param key string the key identifying the particular cache you want to access
     * @param name string the name of the variable in that particular cache
     * @return mixed value of the variable, or false if variable isn't cached
     * @todo make sure we can make this protected
    **/
    public static function isCached($cacheKey, $name)
    {
        if (!isset(self::$cacheCollection[$cacheKey])) {
            // initialize the cache if necessary
            self::$cacheCollection[$cacheKey] = array();
        }
        if (isset(self::$cacheCollection[$cacheKey][$name])) {
            return true;

        // cache storage typically only works with a single cache namespace, so we add our own prefix here
        } elseif (isset(self::$cacheStorage) && empty(self::$isBulkStorage) && self::$cacheStorage->isCached('CORE:'.$cacheKey.':'.$name)) {
            // pre-fetch the value from second-level cache here (if we don't load from bulk storage)
            self::$cacheCollection[$cacheKey][$name] = self::$cacheStorage->getCached('CORE:'.$cacheKey.':'.$name);
            return true;
        }
        return false;
    }

    /**
     * Get the value of a cached variable
     *
     * @param string $key  the key identifying the particular cache you want to access
     * @param string $name the name of the variable in that particular cache
     * @return mixed value of the variable, or null if variable isn't cached
     * @todo make sure we can make this protected
    **/
    public static function getCached($cacheKey, $name)
    {
        if (!isset(self::$cacheCollection[$cacheKey][$name])) {
            // don't fetch the value from second-level cache here
            return;
        }
        return self::$cacheCollection[$cacheKey][$name];
    }

    /**
     * Set the value of a cached variable
     *
     * @param key string the key identifying the particular cache you want to access
     * @param name string the name of the variable in that particular cache
     * @param value string the new value for that variable
     * @return void
     * @todo make sure we can make this protected
    **/
    public static function setCached($cacheKey, $name, $value)
    {
        if (!isset(self::$cacheCollection[$cacheKey])) {
            // initialize cache if necessary
            self::$cacheCollection[$cacheKey] = array();
        }
        self::$cacheCollection[$cacheKey][$name] = $value;
        if (isset(self::$cacheStorage) && empty(self::$isBulkStorage)) {
            // save the value to second-level cache here
            self::$cacheStorage->setCached('CORE:'.$cacheKey.':'.$name, $value);
        }
    }

    /**
     * Delete a cached variable
     *
     * @param key the key identifying the particular cache you want to access
     * @param name the name of the variable in that particular cache
     * @return null
     * @todo remove the double whammy
     * @todo make sure we can make this protected
    **/
    public static function delCached($cacheKey, $name)
    {
        if (isset(self::$cacheCollection[$cacheKey][$name])) {
            unset(self::$cacheCollection[$cacheKey][$name]);
        }
        if (isset(self::$cacheStorage) && empty(self::$isBulkStorage)) {
            // delete the value from second-level cache here
            self::$cacheStorage->delCached('CORE:'.$cacheKey.':'.$name);
        }
    }

    /**
     * Flush a particular cache (e.g. for session initialization)
     *
     * @param  cacheKey the key identifying the particular cache you want to wipe out
     * @return null
     * @todo make sure we can make this protected
    **/
    public static function flushCached($cacheKey)
    {
        if(isset(self::$cacheCollection[$cacheKey])) {
            unset(self::$cacheCollection[$cacheKey]);
        }
        if (isset(self::$cacheStorage) && empty(self::$isBulkStorage)) {
            // CHECKME: not all cache storage supports this in the same way !
            self::$cacheStorage->flushCached('CORE:'.$cacheKey.':');
        }
    }

    /**
     * Set second-level cache storage if you want to keep values for longer than the current HTTP request
     *
     * @param  cacheStorage the cache storage instance you want to use (typically in-memory like apc, memcached, xcache, ...)
     * @param  cacheExpire how long do you want to keep values in second-level cache storage (if the storage supports it)
     * @param  isBulkStorage do we load/save all variables in bulk by cachekey or not ?
     * @return null
    **/
    public static function setCacheStorage($cacheStorage, $cacheExpire = 0, $isBulkStorage = 0)
    {
        self::$cacheStorage = $cacheStorage;
        self::$cacheStorage->setExpire($cacheExpire);
        // CHECKME: do we want to set this here by default ? It doesn't affect most in-memory cache storage...
        self::$cacheStorage->type = 'core';
        // see what's going on in the cache storage ;-)
        //self::$cacheStorage->logfile = sys::varpath() . '/logs/core_cache.txt';
        // FIXME: some in-memory cache storage requires explicit garbage collection !?

        self::$isBulkStorage = $isBulkStorage;
        if ($isBulkStorage) {
            // load from second-level cache storage here
            self::loadBulkStorage();
            // save to second-level cache storage at shutdown
            register_shutdown_function(array('xarCore','saveBulkStorage'));
        }
    }

// CHECKME: work with bulk load / bulk save per cachekey instead of individual gets per cachekey:name ?
//          But what about concurrent updates in bulk then (+ unserialize & autoload too early) ?
//          There doesn't seem to be a big difference in performance using bulk or not, at least with xcache
    public static function loadBulkStorage()
    {
        if (!isset(self::$cacheStorage) || empty(self::$isBulkStorage)) return;
        // get the list of cachekeys
        if (!self::$cacheStorage->isCached('CORE:__cachekeys__')) return;
        $cacheKeys = self::$cacheStorage->getCached('CORE:__cachekeys__');
        if (empty($cacheKeys)) return;
        // load each cachekey from second-level cache
        foreach ($cacheKeys as $cacheKey) {
            $value = self::$cacheStorage->getCached('CORE:'.$cacheKey);
            if (!empty($value)) {
                self::$cacheCollection[$cacheKey] = unserialize($value);
            }
        }
    }
    public static function saveBulkStorage()
    {
        if (!isset(self::$cacheStorage) || empty(self::$isBulkStorage)) return;
        // get the list of cachekeys
        $cacheKeys = array_keys(self::$cacheCollection);
        self::$cacheStorage->setCached('CORE:__cachekeys__', $cacheKeys);
        // save each cachekey to second-level cache
        foreach ($cacheKeys as $cacheKey) {
            $value = serialize(self::$cacheCollection[$cacheKey]);
            self::$cacheStorage->setCached('CORE:'.$cacheKey, $value);
        }
    }
}
?>
