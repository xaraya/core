<?php
/**
 * File: $Id$
 *
 * The Core
 *
 * @package core
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini
*/

/**
 * Core version informations
 *
 * should be upgraded on each release for
 * better control on config settings
 *
 */
define('XARCORE_VERSION_NUM', '0.9.9');
define('XARCORE_VERSION_ID',  'Xaraya');
define('XARCORE_VERSION_SUB', 'adam_baum');

/*
 * System dependencies for (optional) systems
 * ----------------------------------------------
 * | Name           | Depends on                |
 * ----------------------------------------------
 * | ADODB          | nothing                   |
 * | SESSION        | ADODB                     |
 * | CONFIGURATION  | ADODB                     |
 * | USER           | SESSION, ADODB            |
 * | BLOCKS         | CONFIGURATION, ADODB      | (Paul, can you confirm this?)
 * | MODULES        | CONFIGURATION, ADODB      |
 * | EVENTS         | MODULES                   |
 * ----------------------------------------------
 *
 *
 *   ADODB              (00000001)
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

/*
 * Optional systems defines that can be used as parameter for xarCoreInit
 * System dependancies are yet present in the define, so you don't
 * have to care of what for example the SESSION system depends on, if you
 * need it you just pass XARCORE_SYSTEM_SESSION to xarCoreInit and its
 * dependancies will be automatically resolved
 */

//TODO: <besfred> rethink runlevels and make them independant from one another

define('XARCORE_SYSTEM_NONE', 0);
define('XARCORE_SYSTEM_ADODB', 1);
define('XARCORE_SYSTEM_SESSION', 2 | XARCORE_SYSTEM_ADODB);
define('XARCORE_SYSTEM_USER', 4 | XARCORE_SYSTEM_SESSION);
define('XARCORE_SYSTEM_CONFIGURATION', 8 | XARCORE_SYSTEM_ADODB);
define('XARCORE_SYSTEM_BLOCKS', 16 | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_MODULES', 32 | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_ALL', 63); // bit OR of all optional systems

define('XARCORE_BIT_ADODB', 1);
define('XARCORE_BIT_SESSION', 2);
define('XARCORE_BIT_USER', 4 );
define('XARCORE_BIT_CONFIGURATION', 8);
define('XARCORE_BIT_BLOCKS', 16);
define('XARCORE_BIT_MODULES', 32);

/*
 * Debug flags
 */
define('XARDBG_ACTIVE'           , 1);
define('XARDBG_SQL'              , 2);
define('XARDBG_EXCEPTIONS'       , 4);
define('XARDBG_SHOW_PARAMS_IN_BT', 8);
define('XARDBG_INACTIVE'         ,16);
/*
 * xarInclude flags
 */
define ('XAR_INCLUDE_ONCE', 1);
define ('XAR_INCLUDE_MAY_NOT_EXIST', 2);

/**
 * Initializes the core engine
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param integer whatToLoad What optional systems to load.
 * @return bool true
 * @todo <johnny> fix up sitetable prefix when we have a place to store it
 */
function xarCoreInit($whatToLoad = XARCORE_SYSTEM_ALL)
{
    static $current_load_level = XARCORE_SYSTEM_NONE;
    static $first_load = true;
    $new_load_level = $whatToLoad;

    // Make sure it only loads the current load level (or less than the current
    // load level) once.
    if ($whatToLoad <= $current_load_level) {
        if (!$first_load) {
            return true;
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
     * Required subsystems
     *
     * These are always needed, think twice before changing the loading order.
     * By definition the core required subsystems should not be included in the ML
     * system.
     *
     */
    // {ML_dont_parse 'includes/xarLog.php'}
    include 'includes/xarLog.php';
    // {ML_dont_parse 'includes/xarEvt.php'}
    include 'includes/xarEvt.php';
    include 'includes/xarException.php';
    include 'includes/xarVar.php';
    include 'includes/xarServer.php';
    include 'includes/xarMLS.php';
    include 'includes/xarTemplate.php';

    /*
     * If there happens something we want to be able to log it
     *
     */
    $systemArgs = array('loggerName' => xarCore_getSystemVar('Log.LoggerName'),
                        'loggerArgs' => xarCore_getSystemVar('Log.LoggerArgs'),
                        'level' => xarCore_getSystemVar('Log.LogLevel'));
    xarLog_init($systemArgs, $whatToLoad);

    /*
     * Start Exception Handling System
     *
     * Before we do anything make sure we can except out of code in a predictable matter
     *
     */
    $systemArgs = array('enablePHPErrorHandler' => xarCore_getSystemVar('Exception.EnablePHPErrorHandler'));
    xarCES_init($systemArgs, $whatToLoad);
    xarError_init($systemArgs, $whatToLoad);

    /**
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
     * Start Database Connection Handling System
     *
     * Most of the stuff, except for logging, exception and system related things,
     * we want to do in the database, so initialize that as early as possible.
     * It think this is the earliest we can do
     *
     */
    if ($whatToLoad & XARCORE_SYSTEM_ADODB) {
        include 'includes/xarDB.php';

        // Decode encoded DB parameters
        $userName = xarCore_getSystemVar('DB.UserName');
        $password = xarCore_getSystemVar('DB.Password');
        if (xarCore_getSystemVar('DB.Encoded') == '1') {
            $userName = base64_decode($userName);
            $password  = base64_decode($password);
        }
        $systemArgs = array('userName' => $userName,
                            'password' => $password,
                            'databaseHost' => xarCore_getSystemVar('DB.Host'),
                            'databaseType' => xarCore_getSystemVar('DB.Type'),
                            'databaseName' => xarCore_getSystemVar('DB.Name'),
                            'systemTablePrefix' => xarCore_getSystemVar('DB.TablePrefix'),
                            // uncomment this and remove the next line when we can store
                            // site vars that are pre DB
                            //'siteTablePrefix' => xarCore_getSiteVar('DB.TablePrefix'));
                            'siteTablePrefix' => xarCore_getSystemVar('DB.TablePrefix'));
        // Connect to database
        xarDB_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_ADODB;
    }

    /*
     * Start Event Messaging System
     *
     * The event messaging system can be initialized only after the db, but should
     * be as early as possible in place. This system is for *core* events
     *
     */
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
        include 'includes/xarConfig.php';

        // Start Configuration Unit
        $systemArgs = array();
        xarConfig_init($systemArgs, $whatToLoad);

        // Pre-load site config variables
        // CHECKME: see if this doesn't hurt install before activating :-)
        xarConfig_loadVars();

        // Start Variables utilities
        xarVar_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_CONFIGURATION;
    }

    /**
     * Legacy systems
     *
     * Before anything fancy is loaded, let's start the legacy systems
     *
     * @todo <mrb> check if this is on/off by default.
     */
    if (xarConfigGetVar('Site.Core.LoadLegacy') == true){
        include 'includes/pnHTML.php';
        include 'includes/pnLegacy.php';
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
    $systemArgs = array('enableShortURLsSupport' => xarConfigGetVar('Site.Core.EnableShortURLsSupport'),
                        'defaultModuleName' => xarConfigGetVar('Site.Core.DefaultModuleName'),
                        'defaultModuleType' => xarConfigGetVar('Site.Core.DefaultModuleType'),
                        'defaultModuleFunction' => xarConfigGetVar('Site.Core.DefaultModuleFunction'),
                        'generateXMLURLs' => false);
    xarSerReqRes_init($systemArgs, $whatToLoad);


    /*
     * Bring Multi Language System online
     *
     */
    $systemArgs = array('translationsBackend' => xarConfigGetVar('Site.MLS.TranslationsBackend'),
                        'MLSMode' => xarConfigGetVar('Site.MLS.MLSMode'),
                        'defaultLocale' => xarConfigGetVar('Site.MLS.DefaultLocale'),
                        'allowedLocales' => xarConfigGetVar('Site.MLS.AllowedLocales')
                        );
    xarMLS_init($systemArgs, $whatToLoad);



    /*
     * We deal with users through the sessions subsystem
     *
     */
    $anonuid = xarConfigGetVar('Site.User.AnonymousUID');
    // FIXME: <mrb> what's this 1 doing here ?
    $anonuid = !empty($anonuid) ? $anonuid : 1;
    define('_XAR_ID_UNREGISTERED', $anonuid);

    if ($whatToLoad & XARCORE_SYSTEM_SESSION) {
        include 'includes/xarSession.php';

        $systemArgs = array('securityLevel' => xarConfigGetVar('Site.Session.SecurityLevel'),
                            'duration' => xarConfigGetVar('Site.Session.Duration'),
                            'inactivityTimeout' => xarConfigGetVar('Site.Session.InactivityTimeout'));
        xarSession_init($systemArgs, $whatToLoad);

        $whatToLoad ^= XARCORE_BIT_SESSION;
    }

    /**
     * Block subsystem
     *
     */
    // FIXME: This is wrong, should be part of templating
    //        it's a legacy thought, we don't need it anymore

    if ($whatToLoad & XARCORE_SYSTEM_BLOCKS) {
        include 'includes/xarBlocks.php';

        // Start Blocks Support Sytem
        $systemArgs = array();
        xarBlock_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_BLOCKS;
    }


    /**
     * Start Modules Subsystem
     *
     * @todo <mrb> why is this optional?
     * @todo <marco> Figure out how to dynamically compute generateXMLURLs argument based on browser request or XHTML site compliance. For now just pass false.
     * @todo <mrb> i thought it was configurable
     */
    if ($whatToLoad & XARCORE_SYSTEM_MODULES) {
        include 'includes/xarMod.php';
        $systemArgs = array('enableShortURLsSupport' => xarConfigGetVar('Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => false);
        xarMod_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_MODULES;

        // Pre-load themes module variables
        // CHECKME: see if this doesn't hurt install before activating :-)
        xarMod_getVarsByModule('themes');
        xarMod_getVarsByName('SupportShortURLs');
    }

    /**
     * We've got basically all we want, start the interface
     * Start BlockLayout Template Engine
     *
     */
    $systemArgs = array('enableTemplatesCaching' => xarConfigGetVar('Site.BL.CacheTemplates'),
                        'themesBaseDirectory' => xarConfigGetVar('Site.BL.ThemesDirectory'),
                        'defaultThemeDir' => xarModGetVar('themes','default'));
    xarTpl_init($systemArgs, $whatToLoad);


    /**
     * At last, we can give people access to the system.
     *
     * @todo <marcinmilan> review what pasts of the old user system need to be retained
     */
    if ($whatToLoad & XARCORE_SYSTEM_USER) {
        include 'includes/xarUser.php';
        include 'includes/xarSecurity.php';

        // Start User System
        $systemArgs = array('authenticationModules' => xarConfigGetVar('Site.User.AuthenticationModules'));
        xarUser_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_USER;
    }

    // Make the current load level == the new load level
    $current_load_level = $new_load_level;

    // Core initialized register the shutdown function
    register_shutdown_function('xarCore__shutdown_handler');
    return true;
}

/**
 * Default shutdown handler
 *
 *
 */
function xarCore__shutdown_handler()
{
    // Default shutdownhandler, nothing here yet,
    // but i think we could do something here with the
    // connection_aborted() function, signalling that
    // the user prematurely aborted. (by hitting stop or closing browser)
    // Also, the other subsystems can use a similar handler, for example to clean up
    // session tables or removing online status flags etc.
    // A carefully constructed combo with ignore_user_abort() and
    // a check afterward will get all requests atomic which might save
    // some headaches. 

    // This handler is guaranteed to be registered as the last one, which
    // means that is also guaranteed to run last in the sequence of shutdown
    // handlers, the last statement in this function 
    // is guaranteed to be the last statement of Xaraya ;-)
}

/**
 * Returns the relative path name for the var directory
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the var directory path name
 * @todo   move the hardcoded stuff to something configurable
 */
function xarCoreGetVarDirPath()
{
    static $varpath = null;
    if (isset($varpath)) return $varpath;
    if (file_exists('./var/.key.php')) {
        include './var/.key.php';
        $varpath = './var/'.$protectionKey;
    } else {
        $varpath = './var';
    }
    return $varpath;
}

/**
 * Activates the debugger.
 *
 * @access public
 * @global integer xarDebug
 * @global integer xarDebug_sqlCalls
 * @global string xarDebug_startTime
 * @param integer flags bit mask for the debugger flags
 * @return void
 */
function xarCoreActivateDebugger($flags)
{
    $GLOBALS['xarDebug'] = $flags;
    if ($flags & XARDBG_INACTIVE) {
        // Turn off error reporting
        error_reporting(0);
        // Turn off assertion evaluation
        assert_options(ASSERT_ACTIVE, 0);
    } elseif ($flags & XARDBG_ACTIVE) {
        // Proper error reporting
        error_reporting(E_ALL);
        // Activate assertions
        assert_options(ASSERT_ACTIVE, 1);    // Activate when debugging
        assert_options(ASSERT_WARNING, 1);   // Issue a php warning
        assert_options(ASSERT_BAIL, 0);      // Stop processing?
        assert_options(ASSERT_QUIET_EVAL,0); // Quiet evaluation of assert condition?
        assert_options(ASSERT_CALLBACK,'xarException__assertErrorHandler'); // Call this function when the assert fails
        $GLOBALS['xarDebug_sqlCalls'] = 0;
        $lmtime = explode(' ', microtime());
        $GLOBALS['xarDebug_startTime'] = $lmtime[1] + $lmtime[0];
    }
}

/**
 * Check if the debugger is active
 *
 * @access public
 * @global integer xarDebug
 * @return bool true if the debugger is active, false otherwise
 */
function xarCoreIsDebuggerActive()
{
    return $GLOBALS['xarDebug'] & XARDBG_ACTIVE;
}

/**
 * Check for specified debugger flag.
 *
 * @access public
 * @param integer flag the debugger flag to check for activity
 * @return bool true if the flag is active, false otherwise
 */
function xarCoreIsDebugFlagSet($flag)
{
    return ($GLOBALS['xarDebug'] & XARDBG_ACTIVE) && ($GLOBALS['xarDebug'] & $flag);
}

/**
 * Gets a core system variable
 *
 * System variables are REQUIRED to be set, if they cannot be found
 * the system cannot continue. Only use variables for this which are
 * absolutely necessary to be set. Otherwise use other types of variables
 *
 * @access protected
 * @static systemVars array
 * @param string name name of core system variable to get
 * @param boolean returnNull if System variable doesn't exist return null
 */
function xarCore_getSystemVar($name, $returnNull = false)
{
    static $systemVars = NULL;

    if (xarVarIsCached('Core.getSystemVar', $name)) {
        return xarVarGetCached('Core.getSystemVar', $name);
    }
    if (!isset($systemVars)) {
        $fileName = xarCoreGetVarDirPath() . '/config.system.php';
        if (!file_exists($fileName)) {
            xarCore_die("xarCore_getSystemVar: Configuration file not present: ".$fileName);
        }
        include $fileName;
        $systemVars = $systemConfiguration;
    }

    if (!isset($systemVars[$name])) {
        if($returnNull)
        {
            return null;
        } else {
            // FIXME: remove if/when there's some way to upgrade config.system.php or equivalent
            if ($name == 'DB.UseADODBCache') {
                $systemVars[$name] = false;
            } else {
                xarCore_die("xarCore_getSystemVar: Unknown system variable: ".$name);
            }
        }
    }

    xarVarSetCached('Core.getSystemVar', $name, $systemVars[$name]);

    return $systemVars[$name];
}

/**
 * Get a core site variable
 *
 * @access protected
 * @static array siteVars
 * @param string name name of core site variable to get
 * @return mixed variable value
 * @todo investigate the dependency to xarVar
 */
function xarCore_getSiteVar($name)
{
    static $siteVars = NULL;

    if (xarVarIsCached('Core.getSiteVar', $name)) {
        return xarVarGetCached('Core.getSiteVar', $name);
    }

    if (!isset($siteVars)) {
        $configLoader = new xarCore__ConfigFileLoader();
        $serverName = xarServerGetVar('SERVER_NAME');
        $fileName = xarCoreGetVarDirPath() . "/config.$serverName.xml";
        if (!file_exists($fileName)) {
            $fileName = xarCoreGetVarDirPath() . "/config.site.xml";
        }
        $configLoader->load($fileName);
        $siteVars = $configLoader->getConfigVars();
    }
    if (!isset($siteVars[$name])) {
        xarCore_die("xarCore_getSiteVar: Unknown site variable: ".$name);
    }

    xarVarSetCached('Core.getSiteVar', $name, $siteVars[$name]);

    return $siteVars[$name];

}

/**
 * Load a file and capture any php errors
 *
 * @access public
 * @param  string $fileName name of the file to load
 * @param  bool   $flags    can this file only be loaded once, or multiple times? XAR_INCLUDE_ONCE and  XAR_INCLUDE_MAY_NOT_EXIST are the possible flags right now, INCLUDE_MAY_NOT_EXISTS makes the function succeed even in te absense of the file
 * @return bool   true if file was loaded successfully, false on error (with exception set)
 * @todo   Two out of three xarErrorSet calls in core are in this function, maybe it should be in a subsystem or not except at all?
 */
function xarInclude($fileName, $flags = XAR_INCLUDE_ONCE) 
{

    if (!file_exists($fileName)) {
        if ($flags & XAR_INCLUDE_MAY_NOT_EXIST) {
            return true;
        } else {
            $msg = xarML("Could not load file: [#(1)].", $fileName);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD', $msg);
            return false;
        }
    }

    ob_start();

    if ($flags & XAR_INCLUDE_ONCE) {
        $r = include_once($fileName);
    } else {
        $r = include($fileName);
    }

    $error_msg = strip_tags(ob_get_contents());
    ob_end_clean();

    if (empty($r) || !$r) {
        $msg = xarML("Could not load file: [#(1)].\n\n Error Caught:\n #(2)", $fileName, $error_msg);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD', $msg);
        return false;
    }

    return true;
}

/**
 * Error function before Exceptions are loaded
 *
 * @access protected
 * @param string msg message to print as an error
 */
function xarCore_die($msg)
{
    static $dying = false;
    /*
     * Prolly paranoid now, but to prevent looping we keep track if we have already
     * been here.
     */
    if($dying) return;
    $dying = true;

    // This is allowed, in core itself
    // NOTE that this will never be translated
    if (xarCoreIsDebuggerActive()) {
        $msg = nl2br($msg);
$debug = <<<EOD
<br /><br />
<p align="center"><span style="color: blue">Technical information</span></p>
<p>Xaraya has failed to serve the request, and the failure could not be handled.</p>
<p>This is a bad sign and probably means that Xaraya is not configured properly.</p>
<p>The failure reason is: <span style="color: red">$msg</span></p>
EOD;
    } else {
       $debug = '';
    }
$errPage = <<<EOM
<html>
  <head>
    <title>Fatal Error</title>
  </head>
  <body>
    <p>A fatal error occurred while serving your request.</p>
    <p>We are sorry for this inconvenience.</p>
    <p>If this is the first time you see this message, you can try to access the site directly through index.php<br/>
    If you see this message every time you tried to access to this service, it is probable that our server
    is experiencing heavy problems, for this reason we ask you to retry in some hours.<br/>
    If you see this message for days, we ask you to report the unavailablity of service to our webmaster. Thanks.
    </p>
    $debug
  </body>
</html>
EOM;
    echo $errPage;
    // Sorry, this is the end, nothing can be trusted anymore.
    die();
}

/**
 * Check whether a certain API type is allowed
 *
 * Check whether an API type is allowed to load
 * normally the api types are 'user' and 'admin' but modules
 * may define other API types which do not fall in either of
 * those categories. (for example: visual or soap)
 * The list of API types is read from the Core configuration variable
 * Core.AllowedAPITypes.
 *
 * @author Marcel van der Boom marcel@hsdev.com
 * @access protected
 * @param string apiType type of API to check whether allowed to load
 * @return bool
 */
function xarCoreIsApiAllowed($apiType) 
{
    // Testing for an empty API type just returns false
    if (empty($apiType)) return false;
    if (preg_match ("/api$/i", $apiType)) return false;

    // <mrb> Where do we config this again?
    $allowed = xarConfigGetVar('System.Core.AllowedAPITypes');

    // If no API type restrictions are given, return true
    if (empty($allowed) || count($allowed) == 0) return true;
    return in_array($apiType,$allowed);
}

// CORE CLASSES

/**
 * This class loads a configuration file and returns its content
 * in the form of a configuration array
 *
 * @package core
 * @todo park this class somewhere, it is not used, but usefull
 */
class xarCore__ConfigFileLoader
{
    var $curNode;
    var $curData;

    var $parser;

    var $configVars;

    function load($fileName)
    {
        $this->curData = '';
        $this->configVars = array();

        $this->parser = xml_parser_create('US-ASCII');

        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($this->parser, "beginElement", "endElement");
        xml_set_character_data_handler($this->parser, "characterData");

        if (!($fp = fopen($fileName, 'r'))) {
            xarCore_die("xarCore__ConfigFileLoader: cannot open configuration file $fileName.");
        }

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->parser, $data, feof($fp))) {
                $errstr = xml_error_string(xml_get_error_code($this->parser));
                $line = xml_get_current_line_number($this->parser);
                xarCore_die("xarCore__ConfigFileLoader: XML parser error in $fileName: $errstr at line $line.");
                return;
            }
        }

        xml_parser_free($this->parser);
    }

    function getConfigVars()
    {
        return $this->configVars;
    }

    function beginElement($parser, $tag, $attribs)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        if ($tag == 'variable') {
            if (!isset($attribs['name'])) {
                $line = xml_get_current_line_number($this->parser);
                xarCore_die("xarCore__ConfigFileLoader: Invalid config variable in ".
                    "$fileName at line $line: attribute 'name' not found.");
            }
            if (!isset($attribs['type'])) {
                $line = xml_get_current_line_number($this->parser);
                xarCore_die("xarCore__ConfigFileLoader: Invalid config variable in ".
                    "$fileName at line $line: attribute 'type' not found.");
            }
            if ($attribs['type'] != 'string' && $attribs['type'] != 'boolean' &&
                $attribs['type'] != 'args_string' && $attribs['type'] != 'scs_string' &&
                $attribs['type'] != 'integer' && $attribs['type'] != 'double') {
                $line = xml_get_current_line_number($this->parser);
                xarCore_die("xarCore__ConfigFileLoader: Invalid config variable in ".
                    "$fileName at line $line: unknown value for attribute 'type': $attribs[type].");
            }
            if (isset($attribs['encoded']) && $attribs['encoded'] == 'true') {
                if ($attribs['type'] != 'string') {
                    $line = xml_get_current_line_number($this->parser);
                    xarCore_die("xarCore__ConfigFileLoader: Invalid config variable in "
                        ."$fileName at line $line: only variables of type string can be encoded.");
                }
                $encoded = true;
            } else {
                $encoded = false;
            }
            $this->curNode = array($attribs['name'],
                                   $attribs['type'],
                                   $encoded);
        }
    }

    function endElement($parser, $tag)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        if ($tag == 'variable') {
            list($name, $type, $encoded) = $this->curNode;
            $value = $this->curData;
            switch ($type) {
                case 'string':
                    if ($encoded) {
                        $this->configVars[$name] = base64_decode($value);
                    } else {
                        $this->configVars[$name] = $value;
                    }
                    break;
                case 'boolean':
                    if ($value == 'true') {
                        $this->configVars[$name] = true;
                    } else {
                        $this->configVars[$name] = false;
                    }
                    break;
                case 'args_string':
                    $tmp = explode(';', $value);
                    $args = array();
                    foreach($tmp as $arg) {
                        if ($arg != '') {
                            list($k, $v) = explode('=', $arg);
                            $k = trim($k);
                            $v = trim($v);
                            $args[$k] = $v;
                        }
                    }
                    $this->configVars[$name] = $args;
                    break;
                case 'scs_string':
                    $tmp = explode(';', $value);
                    $values = array();
                    foreach($tmp as $arg) {
                        $arg = trim($arg);
                        if ($arg != '') {
                            $values[] = $arg;
                        }
                    }
                    $this->configVars[$name] = $values;
                    break;
                case 'integer':
                    $this->configVars[$name] = (int) $value;
                    break;
                case 'double':
                    $this->configVars[$name] = (double) $value;
                    break;
            }
        }
        $this->curData = '';
    }

    function characterData($parser, $data)
    {
        // FIXME: <marco> consider to replace \n,\r with ''
        $this->curData .= trim($data);
    }

}
?>