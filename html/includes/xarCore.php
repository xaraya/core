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
 * @subpackage The Core
 * @author Marco Canini
*/

/**
 * Core version informations
 *
 * should be upgraded on each release for
 * better control on config settings
 *
 */
define('XARCORE_VERSION_NUM', '.903');
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
define('XARDBG_ACTIVE', 1);
define('XARDBG_SQL', 2);
define('XARDBG_EXCEPTIONS', 4);
define('XARDBG_SHOW_PARAMS_IN_BT', 8);

define('_XAR_ID_UNREGISTERED', '3');

/**
 * Initializes the core engine
 *
 * @author Marco Canini <m.canini@libero.it>
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

    //Comment this line to disable debugging
    //xarCoreActivateDebugger(XARDBG_EXCEPTIONS /*| XARDBG_SQL*/);
    xarCoreActivateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT);
    //xarCoreActivateDebugger(0);

    // Basic systems alway loaded
    // {ML_dont_parse 'includes/xarLog.php'}
    include 'includes/xarLog.php';
    // {ML_dont_parse 'includes/xarEvt.php'}
    include 'includes/xarEvt.php';

    include 'includes/xarException.php';
    // {ML_dont_parse 'includes/xarVar.php'}
    include 'includes/xarVar.php';
    // {ML_dont_parse 'includes/xarServer.php'}
    include 'includes/xarServer.php';
    // {ML_dont_parse 'includes/xarMLS.php'}
    include 'includes/xarMLS.php';
    // {ML_dont_parse 'includes/xarTemplate.php'}
    include 'includes/xarTemplate.php';
    // {ML_dont_parse 'includes/xarTheme.php'}
    include 'includes/xarTheme.php';

    // Start Exception Handling System
    $systemArgs = array('enablePHPErrorHandler' => xarCore_getSystemVar('Exception.EnablePHPErrorHandler'));
    xarException_init($systemArgs, $whatToLoad);

    // Start Logging Facilities
    $systemArgs = array('loggerName' => xarCore_getSystemVar('Log.LoggerName'),
                        'loggerArgs' => xarCore_getSystemVar('Log.LoggerArgs'),
                        'level' => xarCore_getSystemVar('Log.LogLevel'));
    xarLog_init($systemArgs, $whatToLoad);

    // Start Database Connection Handling System
    if ($whatToLoad & XARCORE_SYSTEM_ADODB) {
        // {ML_dont_parse 'includes/xarDB.php'}
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

    // Start Event Messaging System
    $systemArgs = array('loadLevel' => $whatToLoad);
    xarEvt_init($systemArgs, $whatToLoad);

    // Start Configuration System
    if ($whatToLoad & XARCORE_SYSTEM_CONFIGURATION) {
        // {ML_dont_parse 'includes/xarConfig.php'}
        include 'includes/xarConfig.php';

        // Start Configuration Unit
        $systemArgs = array();
        xarConfig_init($systemArgs, $whatToLoad);

        // Start Variables utilities
        xarVar_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_CONFIGURATION;
    }

    // Legacy systems
    if (xarConfigGetVar('Site.Core.LoadLegacy') == true){
        // {ML_dont_parse 'includes/pnHTML.php'}
        include 'includes/pnHTML.php';
        // {ML_dont_parse 'includes/pnLegacy.php'}
        include 'includes/pnLegacy.php';
    }

    // Start HTTP Protocol Server/Request/Response utilities
    $systemArgs = array('enableShortURLsSupport' => xarConfigGetVar('Site.Core.EnableShortURLsSupport'),
                        'defaultModuleName' => xarConfigGetVar('Site.Core.DefaultModuleName'),
                        'defaultModuleType' => xarConfigGetVar('Site.Core.DefaultModuleType'),
                        'defaultModuleFunction' => xarConfigGetVar('Site.Core.DefaultModuleFunction'),
                        'generateXMLURLs' => false);
    xarSerReqRes_init($systemArgs, $whatToLoad);

    // Start Multi Language System
    $systemArgs = array('translationsBackend' => xarConfigGetVar('Site.MLS.TranslationsBackend'),
                        'MLSMode' => xarConfigGetVar('Site.MLS.MLSMode'),
                        'defaultLocale' => xarConfigGetVar('Site.MLS.DefaultLocale'),
                        'allowedLocales' => xarConfigGetVar('Site.MLS.AllowedLocales'));
    xarMLS_init($systemArgs, $whatToLoad);

    // Start Sessions Subsystem
    if ($whatToLoad & XARCORE_SYSTEM_SESSION) {
        // {ML_dont_parse 'includes/xarSession2.php'}
        // FIXME: LOOK AT xarSession2 code it has a catch22 situation!!
        // It wants to store sessions into the database, which is good,
        // but during the installation procedure there is no database
        //include 'includes/xarSession2.php';
        include 'includes/xarSession.php';

        // Start Session Support
        $systemArgs = array('securityLevel' => xarConfigGetVar('Site.Session.SecurityLevel'),
                            'duration' => xarConfigGetVar('Site.Session.Duration'),
                            'inactivityTimeout' => xarConfigGetVar('Site.Session.InactivityTimeout'));
        xarSession_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_SESSION;
    }

    // Start Blocks Subsystem
    if ($whatToLoad & XARCORE_SYSTEM_BLOCKS) {
        // {ML_dont_parse 'includes/xarBlocks.php'}
        include 'includes/xarBlocks.php';

        // Start Blocks Support Sytem
        $systemArgs = array();
        xarBlock_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_BLOCKS;
    }

    // Start Modules Subsystem
    if ($whatToLoad & XARCORE_SYSTEM_MODULES) {
        // {ML_dont_parse 'includes/xarMod.php'}
        include 'includes/xarMod.php';

        // Start Modules Support
        // TODO: <marco> Figure out how to dynamically compute generateXMLURLs argument based on browser request
        // or XHTML site compliance. For now just pass false.
        $systemArgs = array('enableShortURLsSupport' => xarConfigGetVar('Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => false);
        xarMod_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_MODULES;

    }

    // Start BlockLayout Template Engine
    $systemArgs = array('enableTemplatesCaching' => xarConfigGetVar('Site.BL.CacheTemplates'),
                        'themesBaseDirectory' => xarConfigGetVar('Site.BL.ThemesDirectory'),
                        'defaultThemeName' => xarModGetVar('themes','default'));
    xarTpl_init($systemArgs, $whatToLoad);
        // TODO (marcinmilan): review what pasts of the old user system need to be retained
        if ($whatToLoad & XARCORE_SYSTEM_USER) {
        // {ML_dont_parse 'includes/xarUser.php'}
        include 'includes/xarUser.php';
        // {ML_dont_parse 'includes/xarSecurity.php'}
        include 'includes/xarSecurity.php';

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
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return string the var directory path name
 */
function xarCoreGetVarDirPath()
{
    if (file_exists('var/.key.php')) {
        include 'var/.key.php';
        return 'var/'.$protectionKey;
    }
    return 'var';
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
    if ($flags & XARDBG_ACTIVE) {
        // Proper error reporting
        error_reporting(E_ALL);
        // Activate assertions
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 1);
        assert_options(ASSERT_BAIL, 1);

        $GLOBALS['xarDebug_sqlCalls'] = 0;
        $lmtime = explode(' ', microtime());
        $GLOBALS['xarDebug_startTime'] = $lmtime[1] + $lmtime[0];
    } else {
        // Turn off error reporting
        error_reporting(0);
        // Turn off assertion evaluation
        assert_options(ASSERT_ACTIVE, 0);
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
 * @access protected
 * @static systemVars array
 * @param string name name of core system variable to get
 */
function xarCore_getSystemVar($name)
{
    static $systemVars = NULL;


    if (!isset($systemVars)) {
        $fileName = xarCoreGetVarDirPath() . '/config.system.php';
        include $fileName;
        $systemVars = $systemConfiguration;
    }
    if (!isset($systemVars[$name])) {
        xarCore_die("xarCore_getSystemVar: Unknown system variable: ".$name);
    }
    return $systemVars[$name];
}

/**
 * Get a core site variable
 *
 * @access protected
 * @static array siteVars
 * @param string name name of core site variable to get
 * @return mixed variable value
 */
function xarCore_getSiteVar($name)
{
    static $siteVars = NULL;

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
    return $siteVars[$name];

}

/**
 * Dispose the debugger
 *
 * @access protected
 * @global integer xarDebug
 * @global intger xarDebug_sqlCalls
 * @global string xarDebug_startTime
 * @return void
 */
function xarCore_disposeDebugger()
{
    if ($GLOBALS['xarDebug'] & XARDBG_SQL) {
        xarLogMessage("Total SQL queries: $GLOBALS[xarDebug_sqlCalls].");
    }
    if ($GLOBALS['xarDebug'] & XARDBG_ACTIVE) {
        $lmtime = explode(' ', microtime());
        $endTime = $lmtime[1] + $lmtime[0];
        $totalTime = ($endTime - $GLOBALS['xarDebug_startTime']);
        xarLogMessage("Response was served in $totalTime seconds.");
    }
}

/**
 * Error function before Exceptions are loaded
 *
 * @access protected
 * @param string msg message to print as an error
 */
function xarCore_die($msg)
{
    $url = xarServerGetBaseURL() . 'index.php';
    if (xarCoreIsDebuggerActive()) {
        $msg = nl2br($msg);
$debug = <<<EOD
<p>Technical information</p>
<p>Xaraya has failed to serve the request, and the failure could not be handled.</p>
<p>This is a bad sign and probably means that Xaraya is not configured properly.</p>
<p>The failure reason is: $msg</p>
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
    <p>If this is the first time you see this message try to <a href="$url">click here to continue.</a><br/>
    If you see this message every time you tried to access to this service, it is probable that our server
    is experiencing heavy problems, for this reason we ask you to retry in some hours.<br/>
    If you see this message fordays, we ask you to report the unavailablity of service to our webmaster. Thanks.
    </p>
    $debug
  </body>
</html>
EOM;
    echo $errPage;
    xarCore_disposeDebugger();
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
function xarCoreIsApiAllowed($apiType) {
    // Testing for an empty API type just returns false
    if (empty($apiType)) return false;

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
