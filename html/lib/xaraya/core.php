<?php
/**
 * The Core
 *
 * @package core
 * @subpackage core
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
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
define('XARCORE_VERSION_ID',  'Jamaica');
define('XARCORE_VERSION_NUM', '2.2.0');
define('XARCORE_VERSION_SUB', 'post rabiem risus');
define('XARCORE_VERSION_REV', $rev);

/*
 * System dependencies for (optional) systems
 * FIXME: This diagram isn't correct (or at least not detailed enough)
 *
 * NONE                            (00000000)
 * |
 * |- EXCEPTIONS
 * |
 * |- SYSTEMVARS
 * |
 * |- LOG
 * |
 * |- DATABASE                     (00000001)
 *    |
 *    |- AUTOLOAD
 *    |
 *    |- EVENTS
 *    |
 *    |- CONFIGURATION             (00000010)
 *       |
 *       |- LEGACY
 *       |
 *       |- MLS
 *       |
 *       |- MODULES                (00000100)
 *          |
 *          |- SERVER
 *              |
 *              |- TEMPLATE        (00001000)
 *                 |
 *                 |- SESSION      (00010000)
 *                    |
 *                    |- USER      (00100000)
 *                       |                 
 *                       |- BLOCKS (01000000)
 *                       |
 *                       |- HOOKS  (10000000)
 *                        
 * |- ALL                          (11111111)
 */    
/**
 * System dependencies for (optional) systems
 * FIXME: This diagram isn't correct (or at least not detailed enough)
 * ---------------------------------------------------------
 * | Name           | Depends on                  | Define |
 * ---------------------------------------------------------
 * | EXCEPTIONS     | nothing                     |        |
 * | LOG            | nothing                     |        |
 * | SYSTEMVARS     | nothing                     |        |
 * | DATABASE       | SYSTEMVARS                  |    1   |
 * | AUTOLOAD       | nothing                     |        |
 * | EVENTS         | DATABASE                    |        |
 * | CONFIGURATION  | DATABASE                    |    2   |
 * | LEGACY         | CONFIGURATION               |        |
 * | MLS            | CONFIGURATION               |        |
 * | MODULES        | CONFIGURATION               |    4   |
 * | SERVER         | MODULES                     |        |
 * | TEMPLATE       | SERVER                      |    8   |
 * | SESSION        | TEMPLATE                    |   16   |
 * | USER           | SESSION                     |   32   |
 * | BLOCKS         | USER                        |   64   |
 * | HOOKS          | USER                        |  128   |
 * --------------------------------------------------------- 
    **/


/**#@+
 * Optional systems defines that can be used as parameter for xarCoreInit
 * System dependancies are yet present in the define, so you don't
 * have to care of what for example the SESSION system depends on, if you
 * need it you just pass XARCORE_SYSTEM_SESSION to xarCoreInit and its
 * dependancies will be automatically resolved
 *
 * 
 * @todo   bring these under a class as constant
**/
define('XARCORE_BIT_DATABASE',         1);
define('XARCORE_BIT_CONFIGURATION',    2);
define('XARCORE_BIT_MODULES',          4);
define('XARCORE_BIT_TEMPLATES',        8);
define('XARCORE_BIT_SESSION',          16);
define('XARCORE_BIT_USER',             32);
define('XARCORE_BIT_BLOCKS',           64);
define('XARCORE_BIT_HOOKS',            128);
define('XARCORE_BIT_ALL',              255);

define('XARCORE_SYSTEM_NONE',            0);
define('XARCORE_SYSTEM_DATABASE',        XARCORE_BIT_DATABASE);
define('XARCORE_SYSTEM_CONFIGURATION',   XARCORE_BIT_CONFIGURATION | XARCORE_SYSTEM_DATABASE);
define('XARCORE_SYSTEM_MODULES',         XARCORE_BIT_MODULES | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_TEMPLATES',       XARCORE_BIT_TEMPLATES | XARCORE_SYSTEM_MODULES);
define('XARCORE_SYSTEM_SESSION',         XARCORE_BIT_SESSION | XARCORE_SYSTEM_TEMPLATES);
define('XARCORE_SYSTEM_USER',            XARCORE_BIT_USER | XARCORE_SYSTEM_SESSION);
define('XARCORE_SYSTEM_BLOCKS',          XARCORE_BIT_BLOCKS | XARCORE_SYSTEM_USER);
define('XARCORE_SYSTEM_HOOKS',           XARCORE_BIT_HOOKS | XARCORE_SYSTEM_USER);
define('XARCORE_SYSTEM_ALL',             XARCORE_BIT_ALL); 

/**#@+
 * Debug flags
 *
 * 
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
 * 
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
// Load core caching in case we didn't go through xarCache::init()
sys::import('xaraya.caching.core');


/**
 * Convenience class for keeping track of core stuff
 *
 * @todo change xarCore:: calls to xarCoreCache:: and put other core stuff here ?
**/
class xarCore extends xarCoreCache
{
    const GENERATION = 2;
    // The actual version information
    const VERSION_ID  = XARCORE_VERSION_ID;
    const VERSION_NUM = XARCORE_VERSION_NUM;
    const VERSION_SUB = XARCORE_VERSION_SUB;
    const VERSION_REV = XARCORE_VERSION_REV;
    
    const BIT_DATABASE       = XARCORE_BIT_DATABASE;
    const BIT_CONFIGURATION  = XARCORE_BIT_CONFIGURATION;
    const BIT_MODULES        = XARCORE_BIT_MODULES;
    const BIT_TEMPLATES      = XARCORE_BIT_TEMPLATES;
    const BIT_SESSION        = XARCORE_BIT_SESSION;
    const BIT_USER           = XARCORE_BIT_USER;
    const BIT_BLOCKS         = XARCORE_BIT_BLOCKS;
    const BIT_HOOKS          = XARCORE_BIT_HOOKS;
    const BIT_ALL            = XARCORE_BIT_ALL;
    
    const SYSTEM_NONE          = XARCORE_SYSTEM_NONE;
    const SYSTEM_DATABASE      = XARCORE_SYSTEM_DATABASE;
    const SYSTEM_CONFIGURATION = XARCORE_SYSTEM_CONFIGURATION;
    const SYSTEM_MODULES       = XARCORE_SYSTEM_MODULES;
    const SYSTEM_TEMPLATES     = XARCORE_SYSTEM_TEMPLATES;
    const SYSTEM_SESSION       = XARCORE_SYSTEM_SESSION;
    const SYSTEM_USER          = XARCORE_SYSTEM_USER;
    const SYSTEM_BLOCKS        = XARCORE_SYSTEM_BLOCKS;
    const SYSTEM_HOOKS         = XARCORE_SYSTEM_HOOKS;
    const SYSTEM_ALL           = XARCORE_SYSTEM_ALL;    

}

/**
 * Initializes the core engine
 *
 * 
 * @param integer whatToLoad What optional systems to load.
 * @return boolean true
 * @todo <johnny> fix up sitetable prefix when we have a place to store it
**/
function xarCoreInit($whatToLoad = xarCore::SYSTEM_ALL)
{
        static $current_SYSTEM_level = xarCore::SYSTEM_NONE;
        static $first_load = true;

        $new_SYSTEM_level = $whatToLoad;
        
        // Make sure it only loads the current load level (or less than the current load level) once.
        if ($whatToLoad <= $current_SYSTEM_level) {
            if (!$first_load) return true; // Does this ever happen? If so, we might consider an assert
            $first_load = false;
        } else {
            // if we are loading a load level higher than the
            // current one, make sure to XOR out everything
            // that we've already loaded
            $whatToLoad ^= $current_SYSTEM_level;
        }
        /*
         * At this point we should be able to catch all low level errors, so we can start the debugger
         *
         * FLAGS:
         *
         * XARDBG_INACTIVE                  disable  the debugger
         * XARDBG_ACTIVE                        enable   the debugger
         * XARDBG_EXCEPTIONS                debug exceptions
         * XARDBG_SQL                           debug SQL statements
         * XARDBG_SHOW_PARAMS_IN_BT show parameters in the backtrace
         *
         * Flags can be OR-ed together
         */
        /**
         * Start exceptions subsystem
        **/
        xarCoreActivateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT );       
        // xarCoreActivateDebugger(XARDBG_INACTIVE);
        
        /**
         * Load system variables
        **/
        sys::import('xaraya.variables.system');
        
        /*
         * Start logging subsystem
         */
        $systemArgs = array();
        sys::import('xaraya.log');
        xarLog_init($systemArgs);
        
        /**
         * Make sure we can get time for logging
        **/
        try {
                date_default_timezone_set(xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));
        } catch (Exception $e) {
                die('Your configuration file appears to be missing. This usually indicates Xaraya has not been installed. <br/>Please refer to point 4 of the installation instructions <a href="readme.html" target="_blank">here</a>');
        }

        /*
         * Start Database Connection Handling System
         *
         * Most of the stuff, except for logging, exception and system related things,
         * we want to do in the database, so initialize that as early as possible.
         * It think this is the earliest we can do
         *
         */
        if ($whatToLoad & xarCore::SYSTEM_DATABASE) { // yeah right, as if this is optional
    
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
            $whatToLoad ^= xarCore::BIT_DATABASE;
        }

        /*
         * Start autoload
         *
         * Note: we only need this for variable caching for now, but if we generalize autoloading
         *       of Xaraya classes someday, we could initialize this earlier, e.g. in bootstrap ?
         */
    /* CHECKME: initialize autoload based on config vars, or based on modules, or earlier ? */
        if (xarCache::$variableCacheIsEnabled) {
            sys::import('xaraya.autoload');
            xarAutoload::initialize();
        }
    /*
    // Testing of autoload + second-level cache storage - please do not use on live sites
        sys::import('xaraya.caching.storage');
        $cache = xarCache_Storage::getCacheStorage(array('storage' => 'xcache', 'type' => 'core'));
        xarCoreCache::setCacheStorage($cache);
        // For bulk load, we might have to do this after loading the modules, otherwise
        // unserialize + autoload might trigger a function that complains about xarMod:: etc.
        //xarCoreCache::setCacheStorage($cache,0,1);
    */

        /**
         * Start Events Subsystem
        **/
        sys::import('xaraya.events');
        $systemArgs = array();
        xarEvents::init($systemArgs);


         /*
         * Start Configuration System
         *
         * Ok, we can  except, we can log our actions, we can access the db and we can
         * send events out of the core. It's time we start the configuration system, so we
         * can start configuring the framework
         *
         */
        if ($whatToLoad & xarCore::SYSTEM_CONFIGURATION) {
            // Start Variables utilities
            sys::import('xaraya.variables');
            xarVar_init($systemArgs);
            $whatToLoad ^= xarCore::BIT_CONFIGURATION;
    
        // we're about done here - everything else requires configuration, at least to initialize them !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }       

        /**
         * Legacy systems
         *
         * Before anything fancy is loaded, let's start the legacy systems
         *
         */
        if (xarConfigVars::get(null, 'Site.Core.LoadLegacy') == true) {
            sys::import('xaraya.legacy.legacy');
        }
        
        /*
         * At this point we haven't made any assumptions about architecture
         * except that we use a database as storage container.
         *
         */

        /**
         * Start Modules Subsystem
         *
         * @todo <mrb> why is this optional?
         * @todo <marco> Figure out how to dynamically compute generateXMLURLs argument based on browser request or XHTML site compliance. For now just pass true.
         * @todo <mrb> i thought it was configurable
        **/
        if ($whatToLoad & xarCore::SYSTEM_MODULES) {
            sys::import('xaraya.modules');
            $systemArgs = array('enableShortURLsSupport' => xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport'),
                                'generateXMLURLs' => true);
            xarMod::init($systemArgs);
            $whatToLoad ^= xarCore::BIT_MODULES;
    
        // we're about done here - everything else requires modules !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }

    
        /*
         * Bring HTTP Protocol Server/Request/Response utilities into the story
         *
         */
        sys::import('xaraya.server');
        $systemArgs = array('enableShortURLsSupport' => xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => true);
        xarServer::init($systemArgs);
        sys::import('xaraya.mapper.main');
        xarController::init($systemArgs);
    //    xarController::$response->init($systemArgs);
    
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
         * We've got basically all we want, start the interface
         * Start BlockLayout Template Engine
         *
         */
        if ($whatToLoad & xarCore::SYSTEM_TEMPLATES) { 
            sys::import('xaraya.templates');
    
            $systemArgs = array(
                'enableTemplatesCaching' => xarConfigVars::get(null, 'Site.BL.CacheTemplates'),
                'defaultThemeDir'        => xarModVars::get('themes', 'default_theme','default'),
                'generateXMLURLs'        => true
            );

            xarTpl::init($systemArgs);
            $whatToLoad ^= xarCore::BIT_TEMPLATES;
        // we're about done here - everything else requires templates !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }      

        /*
         * We deal with users through the sessions subsystem
         *
         */
        // @todo Assuming a fixed 2 here needs to be reviewed, core is a too low level system to assume this.
        $anonid = xarConfigVars::get(null, 'Site.User.AnonymousUID',2);
        define('_XAR_ID_UNREGISTERED', $anonid);
    
        if ($whatToLoad & xarCore::SYSTEM_SESSION)
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
    
            $whatToLoad ^= xarCore::BIT_SESSION;
        // we're about done here - everything else requires sessions !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }  


        /**
         * At last, we can give people access to the system.
         *
         * @todo <marcinmilan> review what pasts of the old user system need to be retained
        **/
        /**
         * Initialise users, session, templates for GUI functions
        **/
        if ($whatToLoad & xarCore::SYSTEM_USER)
        {
            sys::import('xaraya.users');
            sys::import('xaraya.security');
    
            // Start User System
            $systemArgs = array('authenticationModules' => xarConfigVars::get(null, 'Site.User.AuthenticationModules'));
            xarUser_init($systemArgs);
            $whatToLoad ^= xarCore::BIT_USER;
        // we're about done here - everything else requires Users !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        } 
        
        /*
         * Block subsystem
         *
         */
        // FIXME: This is wrong, should be part of templating
        //        it's a legacy thought, we don't need it anymore
    
        if ($whatToLoad & xarCore::SYSTEM_BLOCKS)
        {
            sys::import('xaraya.blocks');
    
            // Start Blocks Support Sytem
            $systemArgs = array();
            xarBlock::init($systemArgs);
            $whatToLoad ^= xarCore::BIT_BLOCKS;
        // we're about done here - everything else requires templates !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }

               
        /**
         * Start Hooks Subsystem
        **/
        if ($whatToLoad & xarCore::SYSTEM_HOOKS) {
            sys::import('xaraya.hooks');
            $systemArgs = array();
            xarHooks::init($systemArgs);
            $whatToLoad ^= xarCore::BIT_HOOKS;
        // we're about done here - everything else requires hooks !?
        } /*else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }   */            

        // Make the current load level == the new load level
        $current_SYSTEM_level = $new_SYSTEM_level;
        return true;

    // end init();
}

/**
 * Activates the debugger.
 *
 * 
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
 * 
 * @return boolean true if the debugger is active, false otherwise
**/
function xarCoreIsDebuggerActive()
{
    return xarDebug::$flags & XARDBG_ACTIVE;
}

/**
 * Check for specified debugger flag.
 *
 * 
 * @param integer flag the debugger flag to check for activity
 * @return boolean true if the flag is active, false otherwise
**/
function xarCoreIsDebugFlagSet($flag)
{
    return (xarDebug::$flags & XARDBG_ACTIVE) && (xarDebug::$flags & $flag);
}

/**
 * Checks if a certain function was disabled in php.ini
 *
 *
 * 
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
 * @todo this is close to exceptions or logging than core, see also notes earlier
**/
class xarDebug extends Object
{
    public static $flags     = 0; // default off?
    public static $sqlCalls  = 0; // Should be in flags imo
    public static $startTime = 0; // Should not be here at all
}


?>
