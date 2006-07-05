<?php
/**
 * Legacy Functions
 *
 * @package legacy
 * @copyright (C) 2006 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini
*/

/**
 * Exceptions defined by this subsystem
 *
 */
class ApiDeprecationException extends DeprecationExceptions
{ 
    protected $message = "You are trying to use a deprecated API function [#(1)], Replace this call with #(2)";
}

/***********************************************************************
* This file is for legacy functions needed to make it
* easier to use modules from Xaraya version 1.x in the version 2 series
*
* Please don't fill it with useless
* stuff except as wrappers, and also.. please
* do not duplicate constants that already exist in xaraya core
***********************************************************************/

/**********************************************************************
* WARNING: THIS FILE IS A WORK IN PROGRESS!!!!!!!!!!!!!!!!!!!
* Please mark all stuff that you need in this file or file a bug report
*
* Necessary functions to duplicate
* MODULE SYSTEM FUNCTIONS

* DEPRECATED XAR FUNCTIONS
* xarModEmailURL        -> no direct equivalent
* xarVarPrepForStore()  -> use bind vars or dbconn->qstr() method
* xarPage_sessionLess() -> xarPageCache_sessionLess()
* xarPage_httpCacheHeaders() -> xarPageCache_sendHeaders()
* xarVarCleanUntrused   -> use xarVarFetch validations
* xarVarCleanFromInput  -> use xarVarFetch validations
*/


// Prefix Add
function xarGetStatusMsg()
{
    $msg = xarSessionGetVar('statusmsg');
    xarSessionDelVar('statusmsg');
    $errmsg = xarSessionGetVar('errormsg');
    xarSessionDelVar('errormsg');

    // Error message overrides status message
    if (!empty($errmsg)) {
        return $errmsg;
    }
    return $msg;

function xarBlockTypeExists($modName, $blockType)
{
    if (!xarMod::apiLoad('blocks', 'admin')) return;
    $args = array('modName'=>$modName, 'blockType'=>$blockType);
    return xarModAPIFunc('blocks', 'admin', 'block_type_exists', $args);
}

/**
 * get the user's language
 *
 * @return string the name of the user's language
 * @raise DATABASE_ERROR
 */
function xarUserGetLang()
{
    // FIXME: <marco> DEPRECATED?
    $locale = xarUserGetNavigationLocale();
    $data =& xarMLSLoadLocaleData($locale);
    if (!isset($data)) return; // throw back
    return $data['/language/iso3code'];
}

/**
 * Get the user's theme directory path
 *
 * @return string the user's theme directory path if successful, void otherwise
 */
function xarUser_getThemeName()
{
    if (!xarUserIsLoggedIn()) {
        return;
    }
    $themeName = xarUserGetVar('Theme');
    return $themeName;
}

/*
 * Register an instance schema with the security
 * system
 *
 * @access public
 * @param string component the component to add
 * @param string schema the security schema to add
 * @throws ApiDeprecationException
 * Will fail if an attempt is made to overwrite an existing schema
 */
function xarSecAddSchema($component, $schema)
{
    throw new ApiDeprecationException('xarSecAddSchema','the removal of it. The call isnt needed anymore');
}


/**
 * Generates an URL that reference to a module function via Email.
 *
 * @access public
 * @param modName string registered name of module
 * @param modType string type of function
 * @param funcName string module function
 * @param args array of arguments to put on the URL
 * @return mixed absolute URL for call, or false on failure
 */
function xarModEmailURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array())
{
//TODO: <garrett> either deprecate this function or keep it in synch with xarModURL *or* add another param
//      to xarModURL to handle this functionality. See bug #372
// Let's depreciate it for 1.0.0  next release I will remove it.
    if (empty($modName)) {
        return xarServer::getBaseURL() . 'index.php';
    }

    // The arguments
    $urlArgs[] = "module=$modName";
    if ((!empty($modType)) && ($modType != 'user')) {
        $urlArgs[] = "type=$modType";
    }
    if ((!empty($funcName)) && ($funcName != 'main')) {
        $urlArgs[] = "func=$funcName";
    }
    $urlArgs = join('&', $urlArgs);

    $url = "index.php?$urlArgs";

    foreach ($args as $k=>$v) {
        if (is_array($v)) {
            foreach($v as $l=>$w) {
                if (isset($w)) {
                    $url .= "&$k" . "[$l]=$w";
                }
            }
        } elseif (isset($v)) {
            $url .= "&$k=$v";
        }
    }

    // The URL
    return xarServer::getBaseURL() . $url;
}

/**
* Ready database output
 *
 * Gets a variable, cleaning it up such that the text is
 * stored in a database exactly as expected. Can have as many parameters as desired.
 *
 * @deprec 2004-02-18
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @todo are we allowing arrays and objects for real?
 */
function xarVarPrepForStore()
{
    // Issue a WARNING as this function is deprecated
    xarLogMessage('Using deprecated function xarVarPrepForStore, use bind variables instead',XARLOG_LEVEL_WARNING);
    $resarray = array();
    foreach (func_get_args() as $var) {
        
        // Prepare var
        if (!get_magic_quotes_runtime()) {
            // FIXME: allow other than strings?
            $var = addslashes($var);
        }
        
        // Add to array
        array_push($resarray, $var);
    }
    
    // Return vars
    if (func_num_args() == 1) {
        return $resarray[0];
    } else {
        return $resarray;
    }
}

/**
 * Session-less page caching
 *
 * @author mikespub, jsb
 * @access private
 * @return void
 * @deprec 2005-02-01
 */
function xarPage_sessionLess()
{
    xarPageCache_sessionLess();
}

/**
 * Send HTTP headers for page caching (or return 304 Not Modified)
 *
 * @author mikespub, jsb
 * @access private
 * @return void
 * @deprec 2005-02-01
 */
function xarPage_httpCacheHeaders($cache_file)
{
    if (!file_exists($cache_file)) { return; }
    $modtime = filemtime($cache_file);

    xarPageCache_sendHeaders($modtime);
}

/**

 * see if a user is authorised to carry out a particular task
 *
 * @access public
 * @param  integer realm the realm to authorize
 * @param  string component the component to authorize
 * @param  string instance the instance to authorize
 * @param  integer level the level of access required
 * @param  integer userId  user id to check for authorisation
 * @return bool
 * @raise DATABASE_ERROR
 */
function xarSecAuthAction($testRealm, $testComponent, $testInstance, $testLevel, $userId = NULL)
{
    return pnSecAuthAction($testRealm, $testComponent, $testInstance, $testLevel, $userId);
}

/**
 * Cleans a variable.
 *
 *
 * Cleaning it up to try to ensure that hack attacks
 * don't work. Typically used for cleaning variables
 * coming from user input.
 *
 * @access public
 * @param var variable to clean
 * @return string prepared variable
 * @deprecated
 */
function xarVarCleanUntrusted($var)
{
    // Issue a WARNING as this function is deprecated
    xarLogMessage('Using deprecated function xarVarCleanUntrusted, use ??? instead',XARLOG_LEVEL_WARNING);
    $search = array('|</?\s*SCRIPT[^>]*>|si',
                    '|</?\s*FRAME[^>]*>|si',
                    '|</?\s*OBJECT[^>]*>|si',
                    '|</?\s*META[^>]*>|si',
                    '|</?\s*APPLET[^>]*>|si',
                    '|</?\s*LINK[^>]*>|si',
                    '|</?\s*IFRAME[^>]*>|si',
                    '|STYLE\s*=\s*"[^"]*"|si');
    // short open tag <  followed by ? (we do it like this otherwise our qa tests go bonkers)
    $replace = array('');
    // Clean var
    $var = preg_replace($search, $replace, $var);

    return $var;
}
?>
