<?php

/**
 * The Core
 *
 * @package core\core
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
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
 * @package core\core
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
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
    public const VERSION_ID        = 'Bermuda';
    public const VERSION_NUM       = '2.8.1';
    public const VERSION_SUB       = 'olympic';  // continuing the Olympic motto

    public const BIT_DATABASE      = 1;
    public const BIT_CONFIGURATION = 2;
    public const BIT_MODULES       = 4;
    public const BIT_TEMPLATES     = 8;
    public const BIT_SESSION       = 16;
    public const BIT_USER          = 32;
    public const BIT_BLOCKS        = 64;
    public const BIT_HOOKS         = 128;
    public const BIT_ALL           = 255;

    public const SYSTEM_NONE           = 0;
    public const SYSTEM_DATABASE       = self::BIT_DATABASE;
    public const SYSTEM_CONFIGURATION  = self::BIT_CONFIGURATION | self::SYSTEM_DATABASE ;
    public const SYSTEM_MODULES        = self::BIT_MODULES | self::SYSTEM_CONFIGURATION ;
    public const SYSTEM_TEMPLATES      = self::BIT_TEMPLATES | self::SYSTEM_MODULES ;
    public const SYSTEM_SESSION        = self::BIT_SESSION | self::SYSTEM_TEMPLATES ;
    public const SYSTEM_USER           = self::BIT_USER | self::SYSTEM_SESSION ;
    public const SYSTEM_BLOCKS         = self::BIT_BLOCKS | self::SYSTEM_USER ;
    public const SYSTEM_HOOKS          = self::BIT_HOOKS | self::SYSTEM_USER ;
    public const SYSTEM_ALL            = self::BIT_ALL ;

    public const DBG_ACTIVE            = 1;
    public const DBG_SQL               = 2;
    public const DBG_EXCEPTIONS        = 4;
    public const DBG_SHOW_PARAMS_IN_BT = 8;
    public const DBG_INACTIVE          = 16;

    public const CACHEDIR         = '/cache';
    public const DB_CACHEDIR      = '/cache/database';
    public const RSS_CACHEDIR     = '/cache/rss';
    public const TPL_CACHEDIR     = '/cache/templates';
}

/**
 * Sanity check that we are coming in through a normal entry point
 *
 */
if (!class_exists('sys')) {
    throw new Exception("The Xaraya precore was not loaded");
}

/**
 * Now begin loading
 *
 */
// Before we do anything make sure we can except out of code in a predictable matter
if (!class_exists('xarExceptions')) {
    sys::import('xaraya.exceptions');
}
// Load core caching in case we didn't go through xarCache::init()
if (!class_exists('xarCoreCache')) {
    sys::import('xaraya.caching.core');
}

/**
 * Xaraya core class
 *
 * This class is the engine of the Xaraya framework.
 * It is called with each page request and loads the functionality required to process the request.
 *
 * @package core\core
 * @todo clean up duplicate const between xarCore:: and xarConst::
**/
class xarCore extends xarObject
{
    public const GENERATION           = 2;

    // The actual version information
    public const VERSION_ID           = xarConst::VERSION_ID;
    public const VERSION_NUM          = xarConst::VERSION_NUM;
    public const VERSION_SUB          = xarConst::VERSION_SUB;
    public const VERSION_REV          = 'unknown';

    public const BIT_DATABASE         = xarConst::BIT_DATABASE;
    public const BIT_CONFIGURATION    = xarConst::BIT_CONFIGURATION;
    public const BIT_MODULES          = xarConst::BIT_MODULES;
    public const BIT_TEMPLATES        = xarConst::BIT_TEMPLATES;
    public const BIT_SESSION          = xarConst::BIT_SESSION;
    public const BIT_USER             = xarConst::BIT_USER;
    public const BIT_BLOCKS           = xarConst::BIT_BLOCKS;
    public const BIT_HOOKS            = xarConst::BIT_HOOKS;
    public const BIT_ALL              = xarConst::BIT_ALL;

    public const SYSTEM_NONE          = xarConst::SYSTEM_NONE;
    public const SYSTEM_DATABASE      = xarConst::SYSTEM_DATABASE;
    public const SYSTEM_CONFIGURATION = xarConst::SYSTEM_CONFIGURATION;
    public const SYSTEM_MODULES       = xarConst::SYSTEM_MODULES;
    public const SYSTEM_TEMPLATES     = xarConst::SYSTEM_TEMPLATES;
    public const SYSTEM_SESSION       = xarConst::SYSTEM_SESSION;
    public const SYSTEM_USER          = xarConst::SYSTEM_USER;
    public const SYSTEM_BLOCKS        = xarConst::SYSTEM_BLOCKS;
    public const SYSTEM_HOOKS         = xarConst::SYSTEM_HOOKS;
    public const SYSTEM_ALL           = xarConst::SYSTEM_ALL;

    public static $build       = self::VERSION_REV;
    public static $runLevel    = self::SYSTEM_NONE;

    /**
     * Initializes the core engine
     *
     * @param integer $whatToLoad What optional systems to load.
     * @param mixed $context initialize server & session with context
     * @return boolean true
    **/
    public static function xarInit($whatToLoad = self::SYSTEM_ALL, $context = null)
    {
        static $first_load = true;

        $new_SYSTEM_level = $whatToLoad;

        // Make sure it only loads the current load level (or less than the current load level) once.
        if ($whatToLoad <= self::$runLevel) {
            if (!$first_load) {
                return true;
            } // Does this ever happen? If so, we might consider an assert
            $first_load = false;
        } else {
            // if we are loading a load level higher than the
            // current one, make sure to XOR out everything
            // that we've already loaded
            $whatToLoad ^= self::$runLevel;
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
        self::activateDebugger(xarConst::DBG_ACTIVE | xarConst::DBG_EXCEPTIONS | xarConst::DBG_SHOW_PARAMS_IN_BT);

        /**
         * Load system variables
        **/
        sys::import('xaraya.variables.system');

        /*
         * Start the logging subsystem
         */
        sys::import('xaraya.log');
        xarLog::init();

        /**
         * Make sure we can get time for logging
        **/
        try {
            date_default_timezone_set(xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));
        } catch (Exception $e) {
            throw new Exception('Your configuration file appears to be missing. This usually indicates Xaraya has not been installed. <br/>Please refer to point 4 of the installation instructions <a href="readme.html" target="_blank">here</a>');
        }

        /**
         * Get context from globals if not specified (default)
         */
        if (is_null($context)) {
            sys::import('xaraya.context.factory');
            $context = Xaraya\Context\ContextFactory::fromGlobals(__METHOD__);
        }
        // Set context for core services here first + return static services class
        sys::import('xaraya.services.xar');
        $xar = Xaraya\Services\xar::setServicesContext($context);

        /**
         * Start Database Connection Handling System
         *
         * Most of the stuff, except for logging, exception and system related things,
         * we want to do in the database, so initialize that as early as possible.
         * It think this is the earliest we can do
         *
         */
        if ($whatToLoad & self::SYSTEM_DATABASE) { // yeah right, as if this is optional
            sys::import('xaraya.database');
            xarDatabase::init();
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
        $xar->cache()->init();

        // Check that the database was installed before we activate variable caching (we don't need to load it yet)
        if (xarSystemVars::get(sys::CONFIG, 'DB.Installation') != 3) {
            $xar->cache()->variableCache = null;
        }

        if ($xar->cache()->withVariables()) {
            sys::import('xaraya.caching.variable');
        }

        /**
         * Start Events Subsystem
        **/
        sys::import('xaraya.events');
        xarEvents::init();

        xarLog::message("The basic subsystems are loaded", xarLog::LEVEL_NOTICE);

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
            $xar->var()->init();
            $whatToLoad ^= self::BIT_CONFIGURATION;
            // We're about done here - everything else requires configuration, at least to initialize them !?
        } else {
            // Make the current load level == the new load level
            self::$runLevel = $new_SYSTEM_level;
            return true;
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
            xarMod::init();
            $whatToLoad ^= self::BIT_MODULES;
            // We're about done here - everything else requires modules !?
        } else {
            // Make the current load level == the new load level
            self::$runLevel = $new_SYSTEM_level;
            return true;
        }

        /**
         * Bring HTTP Protocol Server/Request/Response utilities into the story
         *
         */
        sys::import('xaraya.server');
        $xar->req()->init([]);
        sys::import('xaraya.mapper.main');
        $xar->ctl()->init();

        /**
         * Bring Multi Language System online
         *
         */
        sys::import('xaraya.mls');
        // FIXME: Site.MLS.MLSMode is NULL during install
        xarMLS::init();

        /*
        // Testing of autoload + second-level cache storage - please do not use on live sites
            sys::import('xaraya.caching.storage');
            $cache = xarCache_Storage::getCacheStorage(array('storage' => 'apcu', 'type' => 'core'));
            $xar->mem()->setCacheStorage($cache);
        */

        /**
         * We've got basically all we want, start the interface
         * Start BlockLayout Template Engine
         *
         */
        if ($whatToLoad & self::SYSTEM_TEMPLATES) {
            sys::import('xaraya.templates');
            xarTpl::init();
            $whatToLoad ^= self::BIT_TEMPLATES;
            // We're about done here - everything else requires templates !?
        } else {
            // Make the current load level == the new load level
            self::$runLevel = $new_SYSTEM_level;
            return true;
        }

        /**
         * We deal with users through the sessions subsystem
         *
         */

        if ($whatToLoad & self::SYSTEM_SESSION) {
            sys::import('xaraya.sessions');
            $xar->session()->init([]);
            $whatToLoad ^= self::BIT_SESSION;
            // We're about done here - everything else requires sessions !?
        } else {
            // Make the current load level == the new load level
            self::$runLevel = $new_SYSTEM_level;
            return true;
        }

        /**
         * At last, we can give people access to the system.
         * Initialise users, session, templates for GUI functions
        **/
        if ($whatToLoad & self::SYSTEM_USER) {
            sys::import('xaraya.users');
            sys::import('xaraya.security');
            // Start User System
            $xar->user()->init();
            $whatToLoad ^= self::BIT_USER;
            // We're about done here - everything else requires Users !?
        } else {
            // Make the current load level == the new load level
            self::$runLevel = $new_SYSTEM_level;
            return true;
        }

        /**
         * Block subsystem
         *
         */
        // FIXME: This is wrong, should be part of templating
        //        it's a legacy thought, we don't need it anymore

        if ($whatToLoad & self::SYSTEM_BLOCKS) {
            sys::import('xaraya.blocks');
            // Start Blocks Support System
            xarBlock::init();
            $whatToLoad ^= self::BIT_BLOCKS;
            // We're about done here - everything else requires templates !?
        } else {
            // Make the current load level == the new load level
            self::$runLevel = $new_SYSTEM_level;
            return true;
        }

        /**
         * Start Hooks Subsystem
        **/
        if ($whatToLoad & self::SYSTEM_HOOKS) {
            sys::import('xaraya.hooks');
            xarHooks::init();
            $whatToLoad ^= self::BIT_HOOKS;
            // We're about done here - everything else requires hooks !?
        } /*else {
            // Make the current load level == the new load level
            self::$runLevel = $new_SYSTEM_level;
            return true;
        }   */

        /**
         * Get the current git revision
         * This is displayed in the base module backend
         * Handy if we're running from a working copy, prolly comment out on distributing
         */
        $path = '../.git/refs/heads/com.xaraya.core.bermuda';
        if (@file_exists($path)) {
            $text = file($path);
            $rev = $text[0];
            self::$build = $rev;
        }

        xarLog::message("The core is loaded", xarLog::LEVEL_NOTICE);

        // Make the current load level == the new load level
        self::$runLevel = $new_SYSTEM_level;
        return true;
    }

    /**
     * Check if a particular subsystem is loaded based on the runLevel
     *
     * @return boolean true if the subsystem is loaded, false otherwise
    **/
    public static function isLoaded($checkLevel = self::SYSTEM_ALL)
    {
        return self::$runLevel & $checkLevel;
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
            } catch (Exception $e) {
                $errLevel = E_ALL;
            }

            error_reporting($errLevel);
            // Activate assertions
            assert_options(ASSERT_ACTIVE, 1);    // Activate when debugging
            assert_options(ASSERT_WARNING, 1);    // Issue a php warning
            assert_options(ASSERT_BAIL, 0);    // Stop processing?
            xarDebug::$sqlCalls = 0;
            xarDebug::$startTime = microtime(true);
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
     * @param integer $flag the debugger flag to check for activity
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

        if (!isset($disabled)) {
            // Fetch the disabled functions as an array.
            // White space is trimmed here too.
            $functions = preg_split('/[\s,]+/', trim(ini_get('disable_functions')));

            if ($functions[0] != '') {
                // Make the function names the keys.
                // Values will be 0, 1, 2 etc.
                $disabled = array_flip($functions);
            } else {
                $disabled = [];
            }
        }

        return (isset($disabled[$funcName]) ? true : false);
    }

    /**
     * This should be the only place where we actually exit or die
     * @param mixed $status
     * @return never|void
     */
    public static function exit($status = 0)
    {
        exit($status);
    }
}

/**
 * Convenience class for keeping track of debugger operation
 *
 * @package core\core
 * @todo this is close to exceptions or logging than core, see also notes earlier
**/
class xarDebug extends xarObject
{
    public static $flags     = 0; // default off?
    public static $sqlCalls  = 0; // Should be in flags imo
    public static $startTime = 0; // Should not be here at all

    public static function setExceptionHandler($exception = '')
    {
        if (empty($exception)) {
            return null;
        }

        if (is_array($exception)) {
            $exception = $exception[0] . '::' . $exception[1];
        }
        xarLog::message("xarDebug: Setting exception handler to $exception", xarLog::LEVEL_DEBUG);

        return set_exception_handler($exception);
    }
}
