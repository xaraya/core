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
define('PNCORE_VERSION_NUM', '0.8');
define('PNCORE_VERSION_ID',  'PostNuke');
define('PNCORE_VERSION_SUB', 'adam_baum');

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
define('PNCORE_SYSTEM_NONE', 0);
define('PNCORE_SYSTEM_ADODB', 1);
define('PNCORE_SYSTEM_SESSION', 3);
define('PNCORE_SYSTEM_USER', 7);
define('PNCORE_SYSTEM_CONFIGURATION', 9);
define('PNCORE_SYSTEM_BLOCKS', 25);
define('PNCORE_SYSTEM_MODULES', 41);
define('PNCORE_SYSTEM_ALL', 63); // bit OR of all optional systems

 /*
 * In order for the bitwise operations to work, we need
 * the specific bits to test against - below
 * are the bits that will be tested against
 */ 
define('PNCORE_BIT_NONE', 0x0);           // (00000000)
define('PNCORE_BIT_ADODB', 0x1);          // (00000001)
define('PNCORE_BIT_SESSION', 0x2);        // (00000010)
define('PNCORE_BIT_USER', 0x4);           // (00000100)
define('PNCORE_BIT_CONFIGURATION', 0x8);  // (00001000)
define('PNCORE_BIT_BLOCKS', 0x10);        // (00010000)
define('PNCORE_BIT_MODULES', 0x20);       // (00100000)

/*
 * Debug flags
 */
define('PNDBG_ACTIVE', 1);
define('PNDBG_SQL', 2);
define('PNDBG_EXCEPTIONS', 4);


/**
 * Start the core engine.
 *
 * @access public
 * @param whatToLoad What optional systems to load.
 * @returns bool
 * @return true on success,false on failure
 */
function pnCoreInit($whatToLoad = PNCORE_SYSTEM_ALL)
{
    // FIXME: <marco> Shouldn't we use include instead of include_once?
    // Since pnCoreInit is supposed to be called once per request there's
    // no need to get this more overhead from include_once
    
    //Comment this line to disable debugging
//    pnCoreActivateDebugger(PNDBG_EXCEPTIONS /*| PNDBG_SQL*/);
    pnCoreActivateDebugger(PNDBG_SQL);

    // Hack for some weird PHP systems that should have the
    // LC_* constants defined, but don't
    if (!defined('LC_TIME')) {
        define('LC_TIME', 'LC_TIME');
    }

    // Basic systems alway loaded
    // {ML_dont_parse 'includes/pnLog.php'}
    include_once 'includes/pnLog.php';
    // {ML_dont_parse 'includes/pnEvt.php'}
    include_once 'includes/pnEvt.php';
    // {ML_dont_parse 'includes/pnException.php'}
    include_once 'includes/pnException.php';
    // {ML_dont_parse 'includes/pnVar.php'}
    include_once 'includes/pnVar.php';
    // {ML_dont_parse 'includes/pnServer.php'}
    include_once 'includes/pnServer.php';
    // {ML_dont_parse 'includes/pnMLS.php'}
    include_once 'includes/pnMLS.php';
    include_once 'includes/pnTemplate.php';

    // Legacy systems
    // {ML_dont_parse 'includes/pnHTML.php'}
    include_once 'includes/pnHTML.php';
    // {ML_dont_parse 'includes/pnLegacy.php'}
    include_once 'includes/pnLegacy.php';

    // Initialise system args array
    //$systemArgs = array();

    if ((int) $whatToLoad & PNCORE_BIT_ADODB) {
        // {ML_dont_parse 'includes/pnDB.php'}
        include_once 'includes/pnDB.php';

        // Decode encoded DB parameters
        $userName = pnCore_getSystemVar('DB.UserName');
        $password = pnCore_getSystemVar('DB.Password');
        $systemArgs = array('userName' => $userName,
                            'password' => $password,
                            'databaseHost' => pnCore_getSystemVar('DB.Host'),
                            'databaseType' => pnCore_getSystemVar('DB.Type'),
                            'databaseName' => pnCore_getSystemVar('DB.Name'),
                            'systemTablePrefix' => pnCore_getSystemVar('DB.TablePrefix'),
                            'siteTablePrefix' => pnCore_getSiteVar('DB.TablePrefix'));
        // Connect to database
        pnDB_init($systemArgs);
    }

    // Start Event Messaging System
    $systemArgs = array('loadLevel' => $whatToLoad);
    pnEvt_init($systemArgs);

    pnEvt_registerEvent('PostBodyStart');
    pnEvt_registerEvent('PreBodyEnd');

    // Start Logging Facilities
    $systemArgs = array('loggerName' => pnCore_getSiteVar('Log.Logger.Name'),
                        'loggerArgs' => pnCore_getSiteVar('Log.Logger.Args'),
                        'level' => pnCore_getSiteVar('Log.Level'));
    pnLog_init($systemArgs);

    // Start Exception Handling System
    $systemArgs = array('enablePHPErrorHandler' => pnCore_getSiteVar('Exception.EnablePHPErrorHandler'));
    pnException_init($systemArgs);

    // Start Variables utilities
    // FIXME: <marco> No more sure of this!
    /*
    $systemArgs = array('allowableHTML' => pnCore_getSiteVar('Var.AllowableHTML'),
                        'fixHTMLEntities' => pnCore_getSiteVar('Var.FixHTMLEntities'),
                        'enableCensoringWords' => pnCore_getSiteVar('Var.EnableCensoringWords'),
                        'censoredWords' => pnCore_getSiteVar('Var.CensoredWords'),
                        'censoredWordsReplacers' => pnCore_getSiteVar('Var.CensoredWordsReplacers'));
    */
    //pnVar_init($systemArgs);

    // Start HTTP Protocol Server/Request/Response utilities
    $systemArgs = array('enableShortURLsSupport' => pnCore_getSiteVar('Core.EnableShortURLsSupport'),
                        'defaultModuleName' => pnCore_getSiteVar('Core.DefaultModuleName'),
                        'defaultModuleType' => pnCore_getSiteVar('Core.DefaultModuleType'),
                        'defaultModuleFunction' => pnCore_getSiteVar('Core.DefaultModuleFunction'));
    pnSerReqRes_init($systemArgs);

    if ((int)$whatToLoad & PNCORE_BIT_SESSION) {
        // {ML_dont_parse 'includes/pnSession.php'}
        include_once 'includes/pnSession.php';

        // Start Session Support
        $systemArgs = array('securityLevel' => pnCore_getSiteVar('Session.SecurityLevel'),
                            'duration' => pnCore_getSiteVar('Session.Duration'),
                            'enableIntranetMode' => pnCore_getSiteVar('Session.EnableIntranetMode'),
                            'inactivityTimeout' => pnCore_getSiteVar('Session.InactivityTimeout'));
        pnSession_init($systemArgs);
    }

    // Start Multi Language System
    $systemArgs = array('translationsBackend' => pnCore_getSiteVar('MLS.TranslationsBackend'),
                        'MLSMode' => pnCore_getSiteVar('MLS.MLSMode'),
                        'defaultLocale' => pnCore_getSiteVar('MLS.DefaultLocale'),
                        'allowedLocales' => pnCore_getSiteVar('MLS.AllowedLocales'));
    pnMLS_init($systemArgs);

    if ((int)$whatToLoad & PNCORE_BIT_CONFIGURATION) {
        include_once 'includes/pnConfig.php';

        // Start Configuration Unit
        $systemArgs = array();
        pnConfig_init($systemArgs);

    // FIXME: <mikespub> Well, whenever you're sure marco...
        pnVar_init(array());
    }

    if ((int)$whatToLoad & PNCORE_BIT_MODULES) {
        include_once 'includes/pnMod.php';

        // Start Modules Support
        // TODO: <marco> Figure out how to dynamically compute generateXMLURLs argument based on browser request
        // or XHTML site compliance. For now just pass false.
        $systemArgs = array('enableShortURLsSupport' => pnCore_getSiteVar('Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => false);
        pnMod_init($systemArgs);
    }

// TODO: move this elsewhere ?
    // allow theme override in URL first
    $themeName = pnVarCleanFromInput('theme');
    if (!empty($themeName)) {
        $themeName = pnVarPrepForOS($themeName);
    }

    if ((int)$whatToLoad & PNCORE_BIT_USER) {
        include_once 'includes/pnUser.php';
        // {ML_dont_parse 'includes/pnSecurity.php'}
        include_once 'includes/pnSecurity.php';

        // Start User System
        $systemArgs = array('authenticationModules' => pnCore_getSiteVar('User.AuthenticationModules'));
        pnUser_init($systemArgs);

        // Retrive user theme name
        if (empty($themeName)) {
            $themeName = pnUser_getThemeName();
        }
    }

    if ((int)$whatToLoad & PNCORE_BIT_BLOCKS) {
        include_once 'includes/pnBlocks.php';
        // Start Blocks Support Sytem
        $systemArgs = array();
        pnBlock_init($systemArgs);
    }

    $systemArgs = array('enableTemplatesCaching' => true);
    if (empty($themeName)) {
        // Use the default theme for this site
        $themeName = pnCore_getSiteVar('BL.Theme.Name');
    }
    $systemArgs['themeDirectory'] = pnCore_getSiteVar('BL.ThemesDirectory') . '/' . $themeName;
    pnTpl_init($systemArgs);

    return true;
}

/**
 * Returns the relative path name for the var directory.
 *
 * @access public
 * @returns string
 * @return the var directory path name
 */
function pnCoreGetVarDirPath()
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
function pnCoreActivateDebugger($flags)
{
    global $pnDebug, $pnDebug_sqlCalls, $pnDebug_startTime;
    $pnDebug = PNDBG_ACTIVE | $flags;
    // Proper error reporting
    error_reporting(E_ALL);
    $pnDebug_sqlCalls = 0;
    $lmtime = explode(' ', microtime());
    $pnDebug_startTime = $lmtime[1] + $lmtime[0];
}

/**
 * Checks if the debugger is active..
 *
 * @access public
 * @returns bool
 * @return true if the debugger is active, false otherwise
 */
function pnCoreIsDebuggerActive()
{
    global $pnDebug;

    return $pnDebug & PNDBG_ACTIVE;
}

/**
 * Checks for specified debugger flag.
 *
 * @access public
 * @param flag the debugger flag to check for activity
 * @returns bool
 * @return true if the flag is active, false otherwise
 */
function pnCoreIsDebugFlagSet($flag)
{
    global $pnDebug;

    return $pnDebug & $flag;
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
function pnCore_getSystemVar($name)
{
    static $systemVars = NULL;
    if (!isset($systemVars)) {
        /*
        $configLoader = new pnCore__ConfigFileLoader();
        $fileName = pnCoreGetVarDirPath() . '/config.system.xml';
        $configLoader->load($fileName);
        $systemVars = $configLoader->getConfigVars();
        */
        $fileName = pnCoreGetVarDirPath() . '/config.system.php';
        include $fileName;
        $systemVars = $systemConfiguration;
    }
    if (!isset($systemVars[$name])) {
        pnCore_die("pnCore_getSystemVar: Unknown system variable: ".$name);
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
function pnCore_getSiteVar($name)
{
    static $siteVars = NULL;
    if (!isset($siteVars)) {
        $configLoader = new pnCore__ConfigFileLoader();
        $serverName = pnServerGetVar('SERVER_NAME');
        $fileName = pnCoreGetVarDirPath() . "/config.$serverName.xml";
        if (!file_exists($fileName)) {
            $fileName = pnCoreGetVarDirPath() . "/config.site.xml";
        }
        $configLoader->load($fileName);
        $siteVars = $configLoader->getConfigVars();
    }
    if (!isset($siteVars[$name])) {
        pnCore_die("pnCore_getSiteVar: Unknown site variable: ".$name);
    }
    return $siteVars[$name];

}

/**
 * Disposes the debugger.
 *
 * @access protected
 * @returns void
 */
function pnCore_disposeDebugger()
{
    global $pnDebug, $pnDebug_sqlCalls, $pnDebug_startTime;
    if ($pnDebug & PNDBG_SQL) {
        pnLogMessage("Total SQL queries: $pnDebug_sqlCalls.");
    }
    if ($pnDebug & PNDBG_ACTIVE) {
        $lmtime = explode(' ', microtime());
        $endTime = $lmtime[1] + $lmtime[0];
        $totalTime = ($endTime - $pnDebug_startTime);
        pnLogMessage("Response was served in $totalTime seconds.");
    }
}

function pnCore_die($msg)
{
    // TODO: <marco> Write a good text here! Can we send the 500 http code from php?
    $errPage = "<html><head><title>Fatal Error</title></head><body><p>
                A fatal error occurred bla bla, we're sorry bla bla, retry,
                or contact us bla bla</p>";
    if (pnCoreIsDebuggerActive()) {
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
class pnCore__ConfigFileLoader
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
            pnCore_die("pnCore__ConfigFileLoader: cannot open configuration file $fileName.");
        }

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->parser, $data, feof($fp))) {
                $errstr = xml_error_string(xml_get_error_code($this->parser));
                $line = xml_get_current_line_number($this->parser);
                pnCore_die("pnCore__ConfigFileLoader: XML parser error in $fileName: $errstr at line $line.");
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
                pnCore_die("pnCore__ConfigFileLoader: Invalid config variable in ".
                    "$fileName at line $line: attribute 'name' not found.");
            }
            if (!isset($attribs['type'])) {
                $line = xml_get_current_line_number($this->parser);
                pnCore_die("pnCore__ConfigFileLoader: Invalid config variable in ".
                    "$fileName at line $line: attribute 'type' not found.");
            }
            if ($attribs['type'] != 'string' && $attribs['type'] != 'boolean' &&
                $attribs['type'] != 'args_string' && $attribs['type'] != 'scs_string' &&
                $attribs['type'] != 'integer' && $attribs['type'] != 'double') {
                $line = xml_get_current_line_number($this->parser);
                pnCore_die("pnCore__ConfigFileLoader: Invalid config variable in ".
                    "$fileName at line $line: unknown value for attribute 'type': $attribs[type].");
            }
            if (isset($attribs['encoded']) && $attribs['encoded'] == 'true') {
                if ($attribs['type'] != 'string') {
                    $line = xml_get_current_line_number($this->parser);
                    pnCore_die("pnCore__ConfigFileLoader: Invalid config variable in "
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
