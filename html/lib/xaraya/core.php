<?php
/**
 * The Core
 *
 * @package core
 * @subpackage core
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 * @todo dependencies and runlevels!
**/

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
 * | EVENTS         | DATABASE                    |        |
 * | CONFIGURATION  | DATABASE                    |    2   |
 * | LEGACY         | CONFIGURATION               |        |
 * | MODULES        | CONFIGURATION               |    4   |
 * | SERVER         | MODULES                     |        |
 * | MLS            | CONFIGURATION               |        |
 * | AUTOLOAD       | nothing                     |        |
 * | TEMPLATE       | SERVER                      |    8   |
 * | SESSION        | TEMPLATE                    |   16   |
 * | USER           | SESSION                     |   32   |
 * | BLOCKS         | USER                        |   64   |
 * | HOOKS          | USER                        |  128   |
 * --------------------------------------------------------- 
 **/

/**
 * @package core
 * @subpackage core
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <marc@luetolf-carroll.com>
**/
class xarConst
{
    /**
     * Core version information
     *
     * should be upgraded on each release for
     * better control on config settings
     *
    **/
    const VERSION_ID        = 'Bermuda';
    const VERSION_NUM       = '2.4.0';
    const VERSION_SUB       = 'altius fortius';

    const BIT_DATABASE      = 1;
    const BIT_CONFIGURATION = 2;
    const BIT_MODULES       = 4;
    const BIT_TEMPLATES     = 8;
    const BIT_SESSION       = 16;
    const BIT_USER          = 32;
    const BIT_BLOCKS        = 64;
    const BIT_HOOKS         = 128;
    const BIT_ALL           = 255;

    const SYSTEM_NONE           = 0;
/*
* TODO: Adjust when we move to PHP 5.6.x
    const SYSTEM_DATABASE       = self::BIT_DATABASE;
    const SYSTEM_CONFIGURATION  = self::BIT_CONFIGURATION | self::SYSTEM_DATABASE ;
    const SYSTEM_MODULES        = self::BIT_MODULES | self::SYSTEM_CONFIGURATION ;
    const SYSTEM_TEMPLATES      = self::BIT_TEMPLATES | self::SYSTEM_MODULES ;
    const SYSTEM_SESSION        = self::BIT_SESSION | self::SYSTEM_TEMPLATES ;
    const SYSTEM_USER           = self::BIT_USER | self::SYSTEM_SESSION ;
    const SYSTEM_BLOCKS         = self::BIT_BLOCKS | self::SYSTEM_USER ;
    const SYSTEM_HOOKS          = self::BIT_HOOKS | self::SYSTEM_USER ;
    const SYSTEM_ALL            = self::BIT_ALL ; 

*/
    const DBG_ACTIVE            = 1; 
    const DBG_SQL               = 2; 
    const DBG_EXCEPTIONS        = 4; 
    const DBG_SHOW_PARAMS_IN_BT = 8; 
    const DBG_INACTIVE          = 16; 

    const CACHEDIR         = '/cache';
    const DB_CACHEDIR      = '/cache/database';
    const RSS_CACHEDIR     = '/cache/rss';
    const TPL_CACHEDIR     = '/cache/templates';
}

define('XARCORE_SYSTEM_NONE',            0);
define('XARCORE_SYSTEM_DATABASE',        xarConst::BIT_DATABASE);
define('XARCORE_SYSTEM_CONFIGURATION',   xarConst::BIT_CONFIGURATION | XARCORE_SYSTEM_DATABASE);
define('XARCORE_SYSTEM_MODULES',         xarConst::BIT_MODULES | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_TEMPLATES',       xarConst::BIT_TEMPLATES | XARCORE_SYSTEM_MODULES);
define('XARCORE_SYSTEM_SESSION',         xarConst::BIT_SESSION | XARCORE_SYSTEM_TEMPLATES);
define('XARCORE_SYSTEM_USER',            xarConst::BIT_USER | XARCORE_SYSTEM_SESSION);
define('XARCORE_SYSTEM_BLOCKS',          xarConst::BIT_BLOCKS | XARCORE_SYSTEM_USER);
define('XARCORE_SYSTEM_HOOKS',           xarConst::BIT_HOOKS | XARCORE_SYSTEM_USER);
define('XARCORE_SYSTEM_ALL',             xarConst::BIT_ALL); 

/**
 * Sanity check that we are coming in through a normal entry point
 *
 */
if(!class_exists('sys')) die("The Xaraya precore was not loaded");

/**
 * Now begin loading
 *
 */
// Before we do anything make sure we can except out of code in a predictable matter
sys::import('xaraya.exceptions');
// Load core caching in case we didn't go through xarCache::init()
sys::import('xaraya.caching.core');

/**
 * Xaraya core class<br/>
 * This class is the engine of the Xaraya framework.
 * It is called with each page request and loads the functionality required to process the request.
 *
 * @todo change xarCore:: calls to xarCoreCache:: and put other core stuff here ?
**/
class xarCore extends xarCoreCache
{
    const GENERATION           = 2;
    
    // The actual version information
    const VERSION_ID           = xarConst::VERSION_ID;
    const VERSION_NUM          = xarConst::VERSION_NUM;
    const VERSION_SUB          = xarConst::VERSION_SUB;
    const VERSION_REV          = 'unknown';
    
    const BIT_DATABASE         = xarConst::BIT_DATABASE;
    const BIT_CONFIGURATION    = xarConst::BIT_CONFIGURATION;
    const BIT_MODULES          = xarConst::BIT_MODULES;
    const BIT_TEMPLATES        = xarConst::BIT_TEMPLATES;
    const BIT_SESSION          = xarConst::BIT_SESSION;
    const BIT_USER             = xarConst::BIT_USER;
    const BIT_BLOCKS           = xarConst::BIT_BLOCKS;
    const BIT_HOOKS            = xarConst::BIT_HOOKS;
    const BIT_ALL              = xarConst::BIT_ALL;
    
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
    
    public static $build       = 'unknown';

    /**
     * Initializes the core engine
     * 
     * @param integer whatToLoad What optional systems to load.
     * @return boolean true
    **/
    public static function xarInit($whatToLoad = self::SYSTEM_ALL)
    {
        static $current_SYSTEM_level = self::SYSTEM_NONE;
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
        /**
         * At this point we should be able to catch all low level errors, so we can start the debugger
         *
         * FLAGS:
         *
         * xarConst::DBG_INACTIVE          disable  the debugger
         * xarConst::DBG_ACTIVE            enable   the debugger
         * xarConst::DBG_EXCEPTIONS        debug exceptions
         * xarConst::DBG_SQL               debug SQL statements
         * xarConst::DBG_SHOW_PARAMS_IN_BT show parameters in the backtrace
         *
         * Flags can be OR-ed together
         */
        /**
         * Start exceptions subsystem
        **/
        self::activateDebugger(xarConst::DBG_ACTIVE | xarConst::DBG_EXCEPTIONS | xarConst::DBG_SHOW_PARAMS_IN_BT );       
    
        /**
         * Load system variables
        **/
        sys::import('xaraya.variables.system');
    
        /*
         * Start the logging subsystem
         */
        $systemArgs = array();
        sys::import('xaraya.log');
        xarLog::init($systemArgs);
    
        /**
         * Make sure we can get time for logging
        **/
        try {
            date_default_timezone_set(xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));
        } catch (Exception $e) {
            die('Your configuration file appears to be missing. This usually indicates Xaraya has not been installed. <br/>Please refer to point 4 of the installation instructions <a href="readme.html" target="_blank">here</a>');
        }

        /**
         * Start Database Connection Handling System
         *
         * Most of the stuff, except for logging, exception and system related things,
         * we want to do in the database, so initialize that as early as possible.
         * It think this is the earliest we can do
         *
         */
        if ($whatToLoad & self::SYSTEM_DATABASE) { // yeah right, as if this is optional
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
            try {
                xarDB_init($systemArgs);
            } catch (Exception $e) {
                // Catch the error here rather than in the subsystem, because we might be connection to different databases
                // and want to cater to possible errors in each
                die("Connection error: a database connection could not be established");
            }
            $whatToLoad ^= self::BIT_DATABASE;
        }

        /**
         * Start autoload
         *
         * Note: we only need this for variable caching for now, but if we generalize autoloading
         *       of Xaraya classes someday, we could initialize this earlier, e.g. in bootstrap ?
         */
    /* CHECKME: initialize autoload based on config vars, or based on modules, or earlier ? */
        sys::import('xaraya.caching');
        xarCache::init();

        // Check that the database was installed before we activate variable caching (we don't need to load it yet)
        if (xarSystemVars::get(sys::CONFIG, 'DB.Installation') != 3) {
            xarCache::$variableCacheIsEnabled = false;
        }

        if (xarCache::$variableCacheIsEnabled) {
            sys::import('xaraya.caching.variable');
        }

        /**
         * Start Events Subsystem
        **/
        sys::import('xaraya.events');
        $systemArgs = array();
        xarEvents::init($systemArgs);

        /**
         * Start Configuration System
         *
         * Ok, we can  except, we can log our actions, we can access the db and we can
         * send events out of the core. It's time we start the configuration system, so we
         * can start configuring the framework
         *
         */
        if ($whatToLoad & self::SYSTEM_CONFIGURATION) {
            // Start Variables utilities
            sys::import('xaraya.variables');
            xarVar_init($systemArgs);
            $whatToLoad ^= self::BIT_CONFIGURATION;
            // We're about done here - everything else requires configuration, at least to initialize them !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }       

        /**
         * Legacy systems
         * Before anything fancy is loaded, let's start the legacy systems
         *
         */
        if (xarConfigVars::get(null, 'Site.Core.LoadLegacy') == true) {
            sys::import('xaraya.legacy.legacy');
        }
    
        /**
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
        if ($whatToLoad & self::SYSTEM_MODULES) {
            sys::import('xaraya.modules');
            $systemArgs = array('enableShortURLsSupport' => xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport'),
                                'generateXMLURLs' => true);
            xarMod::init($systemArgs);
            $whatToLoad ^= self::BIT_MODULES;
            // We're about done here - everything else requires modules !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }

        /**
         * Bring HTTP Protocol Server/Request/Response utilities into the story
         *
         */
        sys::import('xaraya.server');
        $systemArgs = array('enableShortURLsSupport' => xarConfigVars::get(null, 'Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => true);
        xarServer::init($systemArgs);
        sys::import('xaraya.mapper.main');
        xarController::init($systemArgs);

        /**
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
        xarMLS::init($systemArgs);

    /*
    // Testing of autoload + second-level cache storage - please do not use on live sites
        sys::import('xaraya.caching.storage');
        $cache = xarCache_Storage::getCacheStorage(array('storage' => 'xcache', 'type' => 'core'));
        xarCoreCache::setCacheStorage($cache);
        // unserialize + autoload might trigger a function that complains about xarMod:: etc.
        //xarCoreCache::setCacheStorage($cache,0,1);
    */

        /**
         * Assemble the autoload functions
         *
         * @todo <mfl> eventually remove the caching condition
         */
        if (xarCache::$variableCacheIsEnabled) {
            sys::import('xaraya.autoload');
            xarAutoload::initialize();
        }

        /**
         * We've got basically all we want, start the interface
         * Start BlockLayout Template Engine
         *
         */
        if ($whatToLoad & self::SYSTEM_TEMPLATES) { 
            sys::import('xaraya.templates');

            $systemArgs = array(
                'enableTemplatesCaching' => xarConfigVars::get(null, 'Site.BL.CacheTemplates'),
                'defaultThemeDir'        => xarModVars::get('themes', 'default_theme','default'),
                'generateXMLURLs'        => true,
                'defaultDocType'         => xarConfigVars::get(null, 'Site.BL.DocType'),
            );

            xarTpl::init($systemArgs);
            $whatToLoad ^= self::BIT_TEMPLATES;
            // We're about done here - everything else requires templates !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }      

        /**
         * We deal with users through the sessions subsystem
         *
         */
        // @todo Assuming a fixed 2 here needs to be reviewed, core is a too low level system to assume this.
        $anonid = xarConfigVars::get(null, 'Site.User.AnonymousUID',2);
        define('_XAR_ID_UNREGISTERED', $anonid);

        if ($whatToLoad & self::SYSTEM_SESSION)
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

            $whatToLoad ^= self::BIT_SESSION;
            // We're about done here - everything else requires sessions !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }  

        /**
         * At last, we can give people access to the system.
         * Initialise users, session, templates for GUI functions
        **/
        if ($whatToLoad & self::SYSTEM_USER)
        {
            sys::import('xaraya.users');
            sys::import('xaraya.security');

            // Start User System
            $systemArgs = array('authenticationModules' => xarConfigVars::get(null, 'Site.User.AuthenticationModules'));
            xarUser::init($systemArgs);
            $whatToLoad ^= self::BIT_USER;
            // We're about done here - everything else requires Users !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }
    
        /**
         * Block subsystem
         *
         */
        // FIXME: This is wrong, should be part of templating
        //        it's a legacy thought, we don't need it anymore

        if ($whatToLoad & self::SYSTEM_BLOCKS)
        {
            sys::import('xaraya.blocks');

            // Start Blocks Support Sytem
            $systemArgs = array();
            xarBlock::init($systemArgs);
            $whatToLoad ^= self::BIT_BLOCKS;
            // We're about done here - everything else requires templates !?
        } else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }
          
        /**
         * Start Hooks Subsystem
        **/
        if ($whatToLoad & self::SYSTEM_HOOKS) {
            sys::import('xaraya.hooks');
            $systemArgs = array();
            xarHooks::init($systemArgs);
            $whatToLoad ^= self::BIT_HOOKS;
            // We're about done here - everything else requires hooks !?
        } /*else {
            // Make the current load level == the new load level
            $current_SYSTEM_level = $new_SYSTEM_level;
            return true;
        }   */            

        /**
         * Get the current git revision
         * This is displayed in the base module backend
         * Handy if we're running from a working copy, prolly comment out on distributing
		     */
        $path = '../.git/refs/heads/com.xaraya.core.bermuda';
        if(@file_exists($path)) {
            $text = file($path);
            $rev = $text[0];
            self::$build = $rev;
        }

        xarLog::message("The core is loaded", xarLog::LEVEL_INFO);

        // Make the current load level == the new load level
        $current_SYSTEM_level = $new_SYSTEM_level;
        return true;
    }

    /**
     * Activates the debugger.
     *
     * @param integer $flags bit mask for the debugger flags
     * @return void
     * @todo  a big part of this should be in the exception (error handling) subsystem.
    **/
    public static function activateDebugger($flags)
    {
        xarDebug::$flags = $flags;
        if ($flags & xarConst::DBG_INACTIVE) {
            // Turn off error reporting
            error_reporting(0);
            // Turn off assertion evaluation
            assert_options(ASSERT_ACTIVE, 0);
        } elseif ($flags & xarConst::DBG_ACTIVE) {
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
     * @return boolean true if the debugger is active, false otherwise
    **/
    public static function isDebuggerActive()
    {
        return xarDebug::$flags & xarConst::DBG_ACTIVE;
    }

    /**
     * Check for specified debugger flag.
     *
     * @param integer flag the debugger flag to check for activity
     * @return boolean true if the flag is active, false otherwise
    **/
    public static function isDebugFlagSet($flag)
    {
        return (xarDebug::$flags & xarConst::DBG_ACTIVE) && (xarDebug::$flags & $flag);
    }

    /**
     * Checks if a certain function was disabled in php.ini
     *
     * @param string $funcName The function name; case-sensitive
     * @todo this seems out of place here.
    **/
    public static function funcIsDisabled($funcName)
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