<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: The Core
// ----------------------------------------------------------------------

/*
 * Core version informations - should be upgraded on each release for
 * better control on config settings
 */
define('XARCORE_VERSION_NUM', '0.8');
define('XARCORE_VERSION_ID',  'Xaraya');
define('XARCORE_VERSION_SUB', 'adam_baum');

/*
 * System dependencies for optional systems
 * ----------------------------------------------
 * | Name           | Depends on                |
 * ----------------------------------------------
 * | ADODB          | nothing                   |
 * | SESSION        | ADODB                     |
 * | CONFIGURATION  | ADODB                     |
 * | USER           | SESSION, ADODB            |
 * | BLOCKS         | CONFIGURATION, ADODB      | (Paul, can you confirm this?)
 * | MODULES        | CONFIGURATION, ADODB      |
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
 */
 
/*
 * Optional systems defines - below are some 
 * predifined bit combinations taking into account
 * system dependancies. (see system dependancy 
 * diagram above for more info). 
 */
define('XARCORE_SYSTEM_NONE', 0);
define('XARCORE_SYSTEM_ADODB', 1);
define('XARCORE_SYSTEM_SESSION', 3);
define('XARCORE_SYSTEM_USER', 7);
define('XARCORE_SYSTEM_CONFIGURATION', 9);
define('XARCORE_SYSTEM_BLOCKS', 25);
define('XARCORE_SYSTEM_MODULES', 41);
define('XARCORE_SYSTEM_ALL', 63); // bit OR of all optional systems

 /*
 * In order for the bitwise operations to work, we need
 * the specific bits to test against - below
 * are the bits that will be tested against
 */ 
define('XARCORE_BIT_NONE', 0x0);           // (00000000)
define('XARCORE_BIT_ADODB', 0x1);          // (00000001)
define('XARCORE_BIT_SESSION', 0x2);        // (00000010)
define('XARCORE_BIT_USER', 0x4);           // (00000100)
define('XARCORE_BIT_CONFIGURATION', 0x8);  // (00001000)
define('XARCORE_BIT_BLOCKS', 0x10);        // (00010000)
define('XARCORE_BIT_MODULES', 0x20);       // (00100000)

/*
 * Debug flags
 */
define('XARDBG_ACTIVE', 1);
define('XARDBG_SQL', 2);
define('XARDBG_EXCEPTIONS', 4);


/**
 * Start the core engine.
 *
 * @access public
 * @param whatToLoad What optional systems to load.
 * @returns bool
 * @return true on success,false on failure
 */
function xarCoreInit($whatToLoad = XARCORE_SYSTEM_ALL)
{
    // FIXME: <marco> Shouldn't we use include instead of include_once?
    // Since xarCoreInit is supposed to be called once per request there's
    // no need to get this more overhead from include_once
    
    //Comment this line to disable debugging
//    xarCoreActivateDebugger(XARDBG_EXCEPTIONS /*| XARDBG_SQL*/);
    xarCoreActivateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS);
    //xarCoreActivateDebugger(0);

    // Hack for some weird PHP systems that should have the
    // LC_* constants defined, but don't
    if (!defined('LC_TIME')) {
        define('LC_TIME', 'LC_TIME');
    }

    // Basic systems alway loaded
    // {ML_dont_parse 'includes/xarLog.php'}
    include_once 'includes/xarLog.php';
    // {ML_dont_parse 'includes/xarEvt.php'}
    include_once 'includes/xarEvt.php';
    // {ML_dont_parse 'includes/xarException.php'}
    include_once 'includes/xarException.php';
    // {ML_dont_parse 'includes/xarVar.php'}
    include_once 'includes/xarVar.php';
    // {ML_dont_parse 'includes/xarServer.php'}
    include_once 'includes/xarServer.php';
    // {ML_dont_parse 'includes/xarMLS.php'}
    include_once 'includes/xarMLS.php';
    include_once 'includes/xarTemplate.php';

    // Legacy systems
    // {ML_dont_parse 'includes/pnHTML.php'}
    include_once 'includes/pnHTML.php';
    // {ML_dont_parse 'includes/pnLegacy.php'}
    include_once 'includes/pnLegacy.php';

    // Initialise system args array
    //$systemArgs = array();

    if ((int) $whatToLoad & XARCORE_BIT_ADODB) {
        // {ML_dont_parse 'includes/xarDB.php'}
        include_once 'includes/xarDB.php';

        // Decode encoded DB parameters
        $userName = xarCore_getSystemVar('DB.UserName');
        $password = xarCore_getSystemVar('DB.Password');
        $systemArgs = array('userName' => $userName,
                            'password' => $password,
                            'databaseHost' => xarCore_getSystemVar('DB.Host'),
                            'databaseType' => xarCore_getSystemVar('DB.Type'),
                            'databaseName' => xarCore_getSystemVar('DB.Name'),
                            'systemTablePrefix' => xarCore_getSystemVar('DB.TablePrefix'),
                            'siteTablePrefix' => xarCore_getSiteVar('DB.TablePrefix'));
        // Connect to database
        xarDB_init($systemArgs);
    }

    // Start Event Messaging System
    $systemArgs = array('loadLevel' => $whatToLoad);
    xarEvt_init($systemArgs);

    xarEvt_registerEvent('StartBodyTag');
    xarEvt_registerEvent('EndBodyTag');

    // Start Logging Facilities
    $systemArgs = array('loggerName' => xarCore_getSiteVar('Log.LoggerName'),
                        'loggerArgs' => xarCore_getSiteVar('Log.LoggerArgs'),
                        'level' => xarCore_getSiteVar('Log.LogLevel'));
    xarLog_init($systemArgs);

    // Start Exception Handling System
    $systemArgs = array('enablePHPErrorHandler' => xarCore_getSiteVar('Exception.EnablePHPErrorHandler'));
    xarException_init($systemArgs);

    // Start Variables utilities
    // FIXME: <marco> No more sure of this!
    /*
    $systemArgs = array('allowableHTML' => xarCore_getSiteVar('Var.AllowableHTML'),
                        'fixHTMLEntities' => xarCore_getSiteVar('Var.FixHTMLEntities'),
                        'enableCensoringWords' => xarCore_getSiteVar('Var.EnableCensoringWords'),
                        'censoredWords' => xarCore_getSiteVar('Var.CensoredWords'),
                        'censoredWordsReplacers' => xarCore_getSiteVar('Var.CensoredWordsReplacers'));
    */
    //xarVar_init($systemArgs);

    // Start HTTP Protocol Server/Request/Response utilities
    $systemArgs = array('enableShortURLsSupport' => xarCore_getSiteVar('Core.EnableShortURLsSupport'),
                        'defaultModuleName' => xarCore_getSiteVar('Core.DefaultModuleName'),
                        'defaultModuleType' => xarCore_getSiteVar('Core.DefaultModuleType'),
                        'defaultModuleFunction' => xarCore_getSiteVar('Core.DefaultModuleFunction'));
    xarSerReqRes_init($systemArgs);

    if ((int)$whatToLoad & XARCORE_BIT_SESSION) {
        // {ML_dont_parse 'includes/xarSession.php'}
        include_once 'includes/xarSession.php';

        // Start Session Support
        $systemArgs = array('securityLevel' => xarCore_getSiteVar('Session.SecurityLevel'),
                            'duration' => xarCore_getSiteVar('Session.Duration'),
                            'enableIntranetMode' => xarCore_getSiteVar('Session.EnableIntranetMode'),
                            'inactivityTimeout' => xarCore_getSiteVar('Session.InactivityTimeout'),
                            'useOldPHPSessions' => false);
        xarSession_init($systemArgs);
    }

    // Start Multi Language System
    $systemArgs = array('translationsBackend' => xarCore_getSiteVar('MLS.TranslationsBackend'),
                        'MLSMode' => xarCore_getSiteVar('MLS.MLSMode'),
                        'defaultLocale' => xarCore_getSiteVar('MLS.DefaultLocale'),
                        'allowedLocales' => xarCore_getSiteVar('MLS.AllowedLocales'));
    xarMLS_init($systemArgs);

    // allow theme override in URL first
    $themeName = xarVarCleanFromInput('theme');
    if (!empty($themeName)) {
        $themeName = xarVarPrepForOS($themeName);
    }

    if ((int)$whatToLoad & XARCORE_BIT_CONFIGURATION) {
        include_once 'includes/xarConfig.php';

        // Start Configuration Unit
        $systemArgs = array();
        xarConfig_init($systemArgs);

        xarVar_init(array());

        // Get theme from config FIXME: make sure this is site specific
        if (empty($themeName)) {
            $configTheme = xarConfigGetVar('Site.BL.DefaultTheme');
        }
    }

    if ((int)$whatToLoad & XARCORE_BIT_MODULES) {
        include_once 'includes/xarMod.php';

        // Start Modules Support
        // TODO: <marco> Figure out how to dynamically compute generateXMLURLs argument based on browser request
        // or XHTML site compliance. For now just pass false.
        $systemArgs = array('enableShortURLsSupport' => xarCore_getSiteVar('Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => false);
        xarMod_init($systemArgs);
    }

    if ((int)$whatToLoad & XARCORE_BIT_USER) {
        include_once 'includes/xarUser.php';
        // {ML_dont_parse 'includes/xarSecurity.php'}
        include_once 'includes/xarSecurity.php';

        // Start User System
        $systemArgs = array('authenticationModules' => xarCore_getSiteVar('User.AuthenticationModules'));
        xarUser_init($systemArgs);

        // Retrive user theme name
        if (empty($themeName)) {
            $themeName = xarUser_getThemeName();
        }
    }

    if ((int)$whatToLoad & XARCORE_BIT_BLOCKS) {
        include_once 'includes/xarBlocks.php';
        // Start Blocks Support Sytem
        $systemArgs = array();
        xarBlock_init($systemArgs);
    }

    // Might want to reorganize these theme details
    if(empty($themeName) && isset($configTheme)) {
        $themeName = $configTheme;
    }

    if (empty($themeName)) {
        // Use the default theme for this site
        $themeName = xarCore_getSiteVar('BL.DefaultTheme');
    }
    $systemArgs = array('enableTemplatesCaching' => true);
    $systemArgs['themeDirectory'] = xarCore_getSiteVar('BL.ThemesDirectory') . '/' . $themeName;
    xarTpl_init($systemArgs);

    return true;
}

/**
 * Returns the relative path name for the var directory.
 *
 * @access public
 * @returns string
 * @return the var directory path name
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
 * @param flags bit mask for the debugger flags to render actives
 * @returns void
 */
function xarCoreActivateDebugger($flags)
{
    global $xarDebug, $xarDebug_sqlCalls, $xarDebug_startTime;
    $xarDebug = $flags;
    if ($xarDebug & XARDBG_ACTIVE) {
        // Proper error reporting
        error_reporting(E_ALL);
        // Activate assertions
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 1);
        assert_options(ASSERT_BAIL, 1);
        $xarDebug_sqlCalls = 0;
        $lmtime = explode(' ', microtime());
        $xarDebug_startTime = $lmtime[1] + $lmtime[0];
    } else {
        // Turn off error reporting
        error_reporting(0);
        // Turn off assertion evaluation
        assert_options(ASSERT_ACTIVE, 0);
    }
}

/**
 * Checks if the debugger is active..
 *
 * @access public
 * @returns bool
 * @return true if the debugger is active, false otherwise
 */
function xarCoreIsDebuggerActive()
{
    global $xarDebug;

    return $xarDebug & XARDBG_ACTIVE;
}

/**
 * Checks for specified debugger flag.
 *
 * @access public
 * @param flag the debugger flag to check for activity
 * @returns bool
 * @return true if the flag is active, false otherwise
 */
function xarCoreIsDebugFlagSet($flag)
{
    global $xarDebug;

    return ($xarDebug & XARDBG_ACTIVE) && ($xarDebug & $flag);
}

// PROTECTED FUNCTIONS

/**
 * Gets a core system variable..
 *
 * @access protected
 * @param name name of core system variable to get
 * @returns mixed
 * @return variable on success, die with error on failure
 */
function xarCore_getSystemVar($name)
{
    static $systemVars = NULL;
    if (!isset($systemVars)) {
        /*
        $configLoader = new xarCore__ConfigFileLoader();
        $fileName = xarCoreGetVarDirPath() . '/config.system.xml';
        $configLoader->load($fileName);
        $systemVars = $configLoader->getConfigVars();
        */
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
 * Gets a core site variable..
 *
 * @access protected
 * @param name name of core site variable to get
 * @returns bool
 * @return variable on success, die with error on failure
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
 * Disposes the debugger.
 *
 * @access protected
 * @returns void
 */
function xarCore_disposeDebugger()
{
    global $xarDebug, $xarDebug_sqlCalls, $xarDebug_startTime;
    if ($xarDebug & XARDBG_SQL) {
        xarLogMessage("Total SQL queries: $xarDebug_sqlCalls.");
    }
    if ($xarDebug & XARDBG_ACTIVE) {
        $lmtime = explode(' ', microtime());
        $endTime = $lmtime[1] + $lmtime[0];
        $totalTime = ($endTime - $xarDebug_startTime);
        xarLogMessage("Response was served in $totalTime seconds.");
    }
}

function xarCore_die($msg)
{
    // TODO: <marco> Write a good text here! Can we send the 500 http code from php?
    $errPage = "<html><head><title>Fatal Error</title></head><body><p>
                A fatal error occurred bla bla, we're sorry bla bla, retry,
                or contact us bla bla</p>";
    if (xarCoreIsDebuggerActive()) {
        $errPage .= "<p><b>Technical motivation is</b>: " . nl2br($msg) . "</p>";
    }
    $errPage .= "</body></html";
    echo $errPage;
    die();
}

// CORE CLASSES

/**
 * This class loads a configuration file and returns its content
 * in the form of a configuration array
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
