<?php
/**
 * File: $Id$
 *
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 */

/**
 * Public errors
 */
define('XAR_NO_EXCEPTION', 0);
define('XAR_USER_EXCEPTION', 1);
define('XAR_SYSTEM_EXCEPTION', 2);
define('XAR_SYSTEM_MESSAGE', 3);

/**
 * Private core exceptions
 */
define('XAR_PHP_EXCEPTION', 10);
define('XAR_CORE_EXCEPTION', 11);
define('XAR_DATABASE_EXCEPTION', 12);
define('XAR_TEMPLATE_EXCEPTION', 13);

// {ML_include 'includes/exceptions/defaultuserexception.class.php'}
// {ML_include 'includes/exceptions/errorcollection.class.php'}
// {ML_include 'includes/exceptions/exceptionstack.class.php'}
// {ML_include 'includes/exceptions/htmlexceptionrendering.class.php'}
// {ML_include 'includes/exceptions/noexception.class.php'}
// {ML_include 'includes/exceptions/systemexception.class.php'}
// {ML_include 'includes/exceptions/systemmessage.class.php'}
// {ML_include 'includes/exceptions/textexceptionrendering.class.php'}

// {ML_include 'includes/exceptions/defaultuserexception.defaults.php'}
// {ML_include 'includes/exceptions/exception.class.php'}
// {ML_include 'includes/exceptions/exceptionrendering.class.php'}
// {ML_include 'includes/exceptions/systemexception.defaults.php'}
// {ML_include 'includes/exceptions/systemmessage.defaults.php'}

include "includes/exceptions/exceptionstack.class.php";
global $CoreStack, $ErrorStack;
$CoreStack = new xarExceptionStack();
$ErrorStack = new xarExceptionStack();

include "includes/exceptions/systemmessage.class.php";
include "includes/exceptions/systemexception.class.php";
include "includes/exceptions/defaultuserexception.class.php";
include "includes/exceptions/noexception.class.php";
include "includes/exceptions/errorcollection.class.php";

/* Error Handling System implementation */

/**
 * Initializes the Error Handling System
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @return bool true
 */
function xarError_init($systemArgs, $whatToLoad)
{
    xarErrorFree();
    return true;
}

/**
 * Allows the caller to raise an error
 *
 * Valid value for $major paramter are: XAR_NO_EXCEPTION, XAR_USER_EXCEPTION, XAR_SYSTEM_EXCEPTION, XAR_SYSTEM_MESSAGE.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param major integer error major number
 * @param errorID string error identifier
 * @param value error object
 * @return void
 */
function xarExceptionSet($major, $errorID, $value = NULL) { xarErrorSet($major, $errorID, $value); }    // deprecated

function xarErrorSet($major, $errorID, $value = NULL)
{
    global $ErrorStack;

    if ($major != XAR_NO_EXCEPTION &&
        $major != XAR_USER_EXCEPTION &&
        $major != XAR_SYSTEM_EXCEPTION &&
        $major != XAR_SYSTEM_MESSAGE) {
            xarCore_die('Attempting to set an error with an invalid major value: ' . $major);
    }

    $stack = xarException__backTrace();
    if (!is_object($value)) {
        // The error passed in is just a msg or an identifier, try to construct
        // the object here.
        if (is_string($value)) {
            // A msg was passed in, use that
            $value = $value; // possibly redundant
        } else {
            if ($major == XAR_SYSTEM_EXCEPTION) {
                $value = '';
            } else {
                $value = "No further information available.";
            }
        }

        if ($major == XAR_SYSTEM_EXCEPTION) {
            $obj = new SystemException($value);
        } elseif ($major == XAR_USER_EXCEPTION){
            $obj = new DefaultUserException($value);
        } elseif ($major == XAR_SYSTEM_MESSAGE){
            $obj = new UserMessage($value);
        } else {
            $obj = new NoException($value);
        }

    }
    else {
        $obj = $value;
    }

    // At this point we have a nice error object
    // Now add whatever properties are still missing
    $obj->setID($errorID);
    $obj->setStack($stack);
    $obj->major = $major;

    // Stick the object on the error stack
    $ErrorStack->push($obj);
    // If the XARDBG_EXCEPTIONS flag is set we log every raised error.
    // This can be useful in debugging since EHS is not so perfect as a native
    // EHS could be (read damned PHP language :).
    if (xarCoreIsDebugFlagSet(XARDBG_EXCEPTIONS)) {
    // TODO: remove again once xarLogException works
        if ($errorID == "ErrorCollection") $obj = $obj->exceptions[0];
        xarLogMessage("Logged error " . $obj->toString(), XARLOG_LEVEL_ERROR);
        //xarLogException();
    }
}

/**
 * Gets the major number of current error
 *
 * Allows the caller to establish whether an error was raised, and to get the major number of raised error.
 * The major number XAR_NO_EXCEPTION identifies the state in which no error was raised.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return integer the major value of raised error
 */
function xarExceptionMajor() { return xarCurrentErrorType(); }    // deprecated

function xarCurrentErrorType()
{
    global $ErrorStack;
    if ($ErrorStack->isempty()) return false;
    $err = $ErrorStack->peek();
    return $err->getMajor();
}

/**
 * Gets the identifier of current error
 *
 * Returns the error identifier corresponding to the current error.
 * If invoked when no error was raised, a void value is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the error identifier
 */
function xarExceptionId() { return xarCurrentErrorID(); }    // deprecated

function xarCurrentErrorID()
{
    global $ErrorStack;
    if ($ErrorStack->isempty()) return false;
    $err = $ErrorStack->peek();
    return $err->getID();
}

/**
 * Gets the current error object
 *
 * Returns the value corresponding to the current error.
 * If invoked when no error or an error for which there is no associated information was raised, a void value is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return mixed error value object
 */
function xarExceptionValue() { return xarCurrentError(); }    // deprecated

function xarCurrentError()
{
    global $ErrorStack;
    if ($ErrorStack->isempty()) return false;
    return $ErrorStack->peek();
}

/**
 * Resets current error status
 *
 * xarErrorFree is a shortcut for xarErrorSet(XAR_NO_EXCEPTION, NULL, NULL).
 * You must always call this function when you handle a caught error or
 * equivalently you don't throw the error back to the caller.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return void
 */
function xarExceptionFree() { xarErrorFree(); }    // deprecated

function xarErrorFree()
{
    global $ErrorStack;
    $ErrorStack->initialize();
}

/**
 * Handles the current error
 *
 * You must always call this function when you handle a caught error.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return void
 */
function xarExceptionHandled() { xarErrorHandled(); }    // deprecated

function xarErrorHandled()
{
//    if (xarCurrentErrorType() == XAR_NO_EXCEPTION) {
//            xarCore_die('xarExceptionHandled: Invalid major value: XAR_NO_EXCEPTION');
//    }

    global $ErrorStack;
    if (!$ErrorStack->isempty())
    $ErrorStack->pop();
}

/**
 * Renders the current error
 *
 * Returns a string formatted according to the $format parameter that provides all the information
 * available on current error.
 * If there is no error currently raised an empty string is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param format string one of html or text
 * @return string the string representing the raised error
 */
function xarExceptionRender($format) { return xarErrorRender($format); }    // deprecated

function xarErrorRender($format,$thisstack = "ERROR")
{
    global $ErrorStack;
    global $CoreStack;

    if ($thisstack == "ERROR") $stack = $ErrorStack;
    else $stack = $CoreStack;

    while (!$stack->isempty()) {

        $error = $stack->pop();
//echo $error->getMajor();exit;

        switch ($error->getMajor()) {
            case XAR_SYSTEM_EXCEPTION:
                $type = 'System Error';
                $template = "systemerror";
                break;
            case XAR_USER_EXCEPTION:
                $type = 'User Error';
                $template = "usererror";
                break;
            case XAR_SYSTEM_MESSAGE:
                $type = 'System Message';
                $template = "systeminfo";
                break;
            case XAR_NO_EXCEPTION:
                continue 2;
            default:
                continue 2;
        }

        if ($format == 'html') {
            include_once "includes/exceptions/htmlexceptionrendering.class.php";
            $rendering = new HTMLExceptionRendering($error);
        }
        else {
            include_once "includes/exceptions/textexceptionrendering.class.php";
            $rendering = new TextExceptionRendering($error);
        }
        $data = array();
        $data['type'] = $type;
        $data['title'] = $rendering->getTitle();
        $data['short'] = $rendering->getShort();
        $data['long'] = $rendering->getLong();
        $data['hint'] = $rendering->getHint();
        $data['stack'] = $rendering->getStack();
        $data['product'] = $rendering->getProduct();
        $data['component'] = $rendering->getComponent();
    }
    if ($format == 'html') {
        return  xarTplFile('modules/base/xartemplates/message-' . $template . '.xd', $data);
    }
    else {
        return $data;
    }
}

// PRIVATE FUNCTIONS

/**
 * PHP error handler bridge to Xaraya exceptions
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access private
 * @return void
 */
function xarException__phpErrorHandler($errorType, $errorString, $file, $line)
{
    global $CoreStack;

    //Checks for a @ presence in the given line, should stop from setting Xaraya or DB errors
    if (!error_reporting()) {
        return;
    }

    //Newer php versions have a 5th parameter that will give us back the context
    //The variable values during the error...

    $msg = "At: " . $file." (Line: " . $line.")<br/><br/>". $errorString ;

    // Trap for errors that are on the so-called "safe path" for rendering
    // Need to revert to raw HTML here
    if (isset($_GET['func']) && $_GET['func'] == 'systemexit') {
        echo '<font color="red"><b>^Error Condition<br /><br />see below<br /><br /></b></font>';
        $rawmsg = "</table><div><hr /><b>Recursive Error</b><br /><br />";
        $rawmsg .= "Normal Xaraya error processing has stopped because of a recurring PHP error. <br /><br />";
        $rawmsg .= "The last registered error message is: <br /><br />";
        $rawmsg .= "PHP Error code: " . $errorType . "<br /><br />";
        $rawmsg .= $msg . "</div>";
        echo $rawmsg;
        exit;
//        $redirectURL = xarServerGetBaseURL() . "index.php?module=base&func=rawexit";
//        $redirectURL .= "&code=" . $errorType . "&exception=" . urlencode($msg);
//        $header = "Location: $redirectURL";
//        header($header, headers_sent());
    }

    // Make cached files also display their source file if it's a template
    // This is just for convenience when giving support, as people will probably
    // not look in the CACHEKEYS file to mention the template.
    if(isset($GLOBALS['xarTpl_cacheTemplates'])) {
        $sourcetmpl='';
        $base = basename($file,'.php');
        $varDir = xarCoreGetVarDirPath();
        if (file_exists($varDir . '/cache/templates/CACHEKEYS')) {
            $fd = fopen($varDir . '/cache/templates/CACHEKEYS', 'r');
            while($cache_entry = fscanf($fd, "%s\t%s\n")) {
                list($hash, $template) = $cache_entry;
                // Strip the colon
                $hash = substr($hash,0,-1);
                if($hash == $base) {
                    // Found the file, source is $template
                    $sourcetmpl = $template;
                    break;
                }
            }
            fclose($fd);
        }
    }

    if(isset($sourcetmpl) && $sourcetmpl != '') $msg .= "<br/><br/>[".$sourcetmpl."]";
    if (!function_exists('xarModURL')) {
        $rawmsg = "Normal Xaraya error processing has stopped because of an error encountered. <br /><br />";
        $rawmsg .= "The last registered error message is: <br /><br />";
        $rawmsg .= "PHP Error code: " . $errorType . "<br /><br />";
        $rawmsg .= $msg;
        echo $rawmsg;
        exit;
    }
    else {
        xarResponseRedirect(xarModURL('base','user','systemexit',
        array('code' => $errorType,
              'exception' => urlencode($msg))));
    }
}

/**
 * Returns a debug back trace
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access private
 * @return array back trace
 */
function xarException__backTrace()
{
    static $btFuncName = NULL;
    if ($btFuncName === NULL) {
        if (function_exists('xdebug_enable')) {
            xdebug_enable();
            $btFuncName = 'xarException__xdebugBackTrace';
        } elseif (function_exists('debug_backtrace')) {
            $btFuncName = 'debug_backtrace';
        } else {
            $btFuncName = '';
        }
    }
    if ($btFuncName === '') return array();
    $stack = $btFuncName();
    return $stack;
}

/**
 * Returns a debug back trace using xdebug
 *
 * Converts a xdebug stack trace to a valid back trace.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access private
 * @return array back trace
 */
function xarException__xdebugBackTrace()
{
    $stack = xdebug_get_function_stack();
    // Performs some action to make $stack conformant with debug_backtrace
    array_shift($stack); // Drop {main}
    array_pop($stack); // Drop xarException__xdebugBackTrace
    if (xarCoreIsDebugFlagSet(XARDBG_SHOW_PARAMS_IN_BT)) {
        for($i = 0; $i < count($stack); $i++) {
            $stack[$i]['args'] = $stack[$i]['params'];
        }
    }
    return array_reverse($stack);
}

/**
 * The Core Exceptions System
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access private
 */
function xarCES_init($args, $whatToLoad)
{
    global $CoreStack;

    // The check for xdebug_enable is not necessary here, we want the handler enabled on the flag, period.
    if ($args['enablePHPErrorHandler'] == true ) { // && !function_exists('xdebug_enable')) {
        set_error_handler('xarException__phpErrorHandler');
    }

    $CoreStack->initialize();
    return true;
}
function xarCoreExceptionFree()
{
    global $CoreStack;
    $CoreStack->initialize();
}
function isCoreException()
{
    global $CoreStack;
    return $CoreStack->size() > 1;
}

?>
