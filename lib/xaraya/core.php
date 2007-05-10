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
 * @todo seems that defines are hoggers, move them to class constants!
**/
// For migration purposes, cos we're lazy
define('XARCORE_GENERATION',2);

// The actual version information
define('XARCORE_VERSION_NUM', '[ongoing development version]');
define('XARCORE_VERSION_ID',  'Xaraya 2 series');
define('XARCORE_VERSION_SUB', 'etiam infractus');

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
 * ----------------------------------------------
 * | Name           | Depends on                |
 * ----------------------------------------------
 * | DATABASE       | nothing                   |
 * | SESSION        | DATABASE                  |
 * | CONFIGURATION  | DATABASE                  |
 * | USER           | SESSION, DATABASE         |
 * | BLOCKS         | CONFIGURATION, DATABASE   |
 * | MODULES        | CONFIGURATION, DATABASE   |
 * | EVENTS         | MODULES                   |
 * ----------------------------------------------
 *
 *
 *   DATABASE           (00000001)
 *   |
 *   |- SESSION         (00000011)
 *   |  |
 *   |  |- USER         (00000111)
 *   |
 *   |- CONFIGURATION   (00001001)
 *      |
 *      |- BLOCKS       (00011001)
 *      |
 *      |- MODULES      (00101001)
 *
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
define('XARCORE_SYSTEM_SESSION'      , 2 | XARCORE_SYSTEM_DATABASE);
define('XARCORE_SYSTEM_USER'         , 4 | XARCORE_SYSTEM_SESSION);
define('XARCORE_SYSTEM_CONFIGURATION', 8 | XARCORE_SYSTEM_DATABASE);
define('XARCORE_SYSTEM_BLOCKS'       , 16 | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_MODULES'      , 32 | XARCORE_SYSTEM_CONFIGURATION);
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

    // Make sure it only loads the current load level (or less than the current
    // load level) once.
    if ($whatToLoad <= $current_load_level) {
        if (!$first_load) {
            return true; // Does this ever happen? If so, we might consider an assert
        } else {
            $first_load = false;
        }
    } else {
        // if we are loading a load level higher than the
        // current one, make sure to XOR out everything
        // that we've already loaded
        $whatToLoad ^= $current_load_level;
    }

    /*
     * Start the different subsystems
     */

    /*
     * Start Exception Handling System
     *
     * Before we do anything make sure we can except out of code in a predictable matter
     *
     */
    sys::import('xaraya.exceptions');
    $systemArgs = array();
    xarError_init($systemArgs, $whatToLoad);

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
    xarCoreActivateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT );

    /*
     * If there happens something we want to be able to log it
     *
     */
    // {ML_dont_parse 'includes/xarLog.php'}
    sys::import('xaraya.log');
    xarLog_init($systemArgs, $whatToLoad);

    /*
     * Start Database Connection Handling System
     *
     * Most of the stuff, except for logging, exception and system related things,
     * we want to do in the database, so initialize that as early as possible.
     * It think this is the earliest we can do
     *
     */
    if ($whatToLoad & XARCORE_SYSTEM_DATABASE) { // yeah right, as if this is optional
        sys::import('xaraya.xarDB');

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
                            'databaseHost' => xarSystemVars::get(sys::CONFIG, 'DB.Host'),
                            'databaseType' => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                            'databaseName' => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                            'persistent' => $persistent,
                            'systemTablePrefix' => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'),
                            'siteTablePrefix' => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'));
        // Connect to database
        xarDB_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_DATABASE;
    }

    /*
     * Start Event Messaging System
     *
     * The event messaging system can be initialized only after the db, but should
     * be as early as possible in place. This system is for *core* events
     *
     */
    // {ML_dont_parse 'includes/events.php'}
    sys::import('xaraya.events');
    $systemArgs = array('loadLevel' => $whatToLoad);
    xarEvt_init($systemArgs, $whatToLoad);


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
        xarVar_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_CONFIGURATION;
    }

    /**
     * Legacy systems
     *
     * Before anything fancy is loaded, let's start the legacy systems
     *
     */
    if (xarConfigGetVar('Site.Core.LoadLegacy') == true) {
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
    sys::import('xaraya.xarServer');
    $systemArgs = array('enableShortURLsSupport' => xarConfigGetVar('Site.Core.EnableShortURLsSupport'),
                        'defaultModuleName'      => xarConfigGetVar('Site.Core.DefaultModuleName'),
                        'defaultModuleType'      => xarConfigGetVar('Site.Core.DefaultModuleType'),
                        'defaultModuleFunction'  => xarConfigGetVar('Site.Core.DefaultModuleFunction'),
                        'generateXMLURLs' => true);
    xarSerReqRes_init($systemArgs, $whatToLoad);


    /*
     * Bring Multi Language System online
     *
     */
    sys::import('xaraya.xarMLS');
    // FIXME: Site.MLS.MLSMode is NULL during install
    $systemArgs = array('MLSMode'             => xarConfigGetVar('Site.MLS.MLSMode'),
//                        'translationsBackend' => xarConfigGetVar('Site.MLS.TranslationsBackend'),
                        'translationsBackend' => 'xml2php',
                        'defaultLocale'       => xarConfigGetVar('Site.MLS.DefaultLocale'),
                        'allowedLocales'      => xarConfigGetVar('Site.MLS.AllowedLocales'),
                        'defaultTimeZone'     => xarConfigGetVar('Site.Core.TimeZone'),
                        'defaultTimeOffset'   => xarConfigGetVar('Site.MLS.DefaultTimeOffset'),
                        );
    xarMLS_init($systemArgs, $whatToLoad);



    /*
     * We deal with users through the sessions subsystem
     *
     */
    $anonuid = xarConfigGetVar('Site.User.AnonymousUID');
    // fall back to default uid 2 during installation (cfr. bootstrap function)
    $anonuid = !empty($anonuid) ? $anonuid : 2;
    define('_XAR_ID_UNREGISTERED', $anonuid);

    if ($whatToLoad & XARCORE_SYSTEM_SESSION)
    {
        sys::import('xaraya.xarSession');

        $systemArgs = array(
            'securityLevel'     => xarConfigGetVar('Site.Session.SecurityLevel'),
            'duration'          => xarConfigGetVar('Site.Session.Duration'),
            'inactivityTimeout' => xarConfigGetVar('Site.Session.InactivityTimeout'),
            'cookieName'        => xarConfigGetVar('Site.Session.CookieName'),
            'cookiePath'        => xarConfigGetVar('Site.Session.CookiePath'),
            'cookieDomain'      => xarConfigGetVar('Site.Session.CookieDomain'),
            'refererCheck'      => xarConfigGetVar('Site.Session.RefererCheck'));
        xarSession_init($systemArgs, $whatToLoad);

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
        xarBlock_init($systemArgs, $whatToLoad);
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
        sys::import('xaraya.xarMod');
        $systemArgs = array('enableShortURLsSupport' => xarConfigGetVar('Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => true);
        xarMod::init($systemArgs);
        $whatToLoad ^= XARCORE_BIT_MODULES;
    }

    /*
     * We've got basically all we want, start the interface
     * Start BlockLayout Template Engine
     *
     */
    sys::import('xaraya.xarTemplate');

    $systemArgs = array(
        'enableTemplatesCaching' => xarConfigGetVar('Site.BL.CacheTemplates'),
        'themesBaseDirectory'    => xarConfigGetVar('Site.BL.ThemesDirectory'),
        'defaultThemeDir'        => xarModVars::get('themes','default'),
        'generateXMLURLs'        => true
    );

    xarTpl_init($systemArgs, $whatToLoad);
    $whatToLoad ^= XARCORE_BIT_TEMPLATE;


    /**
     * At last, we can give people access to the system.
     *
     * @todo <marcinmilan> review what pasts of the old user system need to be retained
    **/
    if ($whatToLoad & XARCORE_SYSTEM_USER)
    {
        sys::import('xaraya.xarUser');
        sys::import('xaraya.xarSecurity');
//        xarSecurity_init();
        // Start User System
        $systemArgs = array('authenticationModules' => xarConfigGetVar('Site.User.AuthenticationModules'));
        xarUser_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_USER;
    }

    // Make the current load level == the new load level
    $current_load_level = $new_load_level;
    return true;
}

/**
 * Returns the relative path name for the var directory
 *
 * @access public
 * @return string the var directory path name
 * @deprec replaced by sys::varpath()
 * @see    sys
**/
function xarCoreGetVarDirPath()
{
    return sys::varpath();
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
 * Wrapper functions to support Xaraya 1 API for systemvars
 *
 * @todo this was a protected function by mistake i think
 * @deprec replaced by xarSystemVars
 * @see    xarSystemVars
**/
function xarCore_getSystemVar($name)
{
    sys::import('xaraya.variables.system');
    return xarSystemVars::get(null, $name);
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
    private static $cacheCollection = array();

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
            self::$cacheCollection[$cacheKey] = array();
            return false;
        }
        return isset(self::$cacheCollection[$cacheKey][$name]);
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
            self::$cacheCollection[$cacheKey] = array();
        }
        self::$cacheCollection[$cacheKey][$name] = $value;
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
        //This unsets the key that said that collection had already been retrieved

        //Seems to have caused a problem because of the expected behaviour of the old code
        //FIXME: Change how this works for a mainstream function, stop the hacks
        if (isset(self::$cacheCollection[$cacheKey][0])) {
            unset(self::$cacheCollection[$cacheKey][0]);
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
    }
}
?>
