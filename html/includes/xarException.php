<?php
/**
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
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

$here=dirname(__FILE__);
include "$here/exceptions/exceptionstack.class.php";

include "$here/exceptions/systemmessage.class.php";
include "$here/exceptions/systemexception.class.php";
include "$here/exceptions/defaultuserexception.class.php";
include "$here/exceptions/noexception.class.php";
include "$here/exceptions/errorcollection.class.php";

global $CoreStack, $ErrorStack;

/* Error Handling System implementation */

/**
 * Initializes the Error Handling System
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @return bool true
 * @todo   can we move the stacks above into the init?
 */
function xarError_init(&$systemArgs, $whatToLoad)
{
    global $CoreStack,$ErrorStack;

    // The check for xdebug_enable is not necessary here, we want the handler enabled on the flag, period.
    if ($systemArgs['enablePHPErrorHandler'] == true ) { // && !function_exists('xdebug_enable')) {
        set_error_handler('xarException__phpErrorHandler');
    }

    $CoreStack = new xarExceptionStack();
    $CoreStack->initialize();

    $ErrorStack = new xarExceptionStack();
    xarErrorFree();

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarError__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for error subsystem
 *
 * @access private
 */
function xarError__shutdown_handler()
{
    //xarLogMessage("xarError shutdown handler");
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
        xarLogMessage("Logged error: " . $obj->toString(), XARLOG_LEVEL_ERROR);
        if (!empty($stack) && $major != XAR_USER_EXCEPTION)
            xarLogMessage(
                "Logged error backtrace: \n" . xarException__formatBacktrace($stack),
                XARLOG_LEVEL_ERROR);
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @return string the error identifier
 */
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @return mixed error value object
 */
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @return void
 */
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
 * @return voidx
 */
function xarErrorHandled()
{
//    if (xarCurrentErrorType() == XAR_NO_EXCEPTION) {
//            xarCore_die('xarErrorHandled: Invalid major value: XAR_NO_EXCEPTION');
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
 * @param format string one of template or plain
 * @param stacktype string one of CORE or ERROR
 * @return string the string representing the raised error
 */
function xarErrorRender($format,$stacktype = "ERROR")
{
    assert('$format == "template" || $format == "rawhtml" || $format == "text"; /* Improper format passed to xarErrorRender */');

    $msgs = xarException__formatStack($format,$stacktype);
    $error = $msgs[0];

    switch ($error->getMajor()) {
        case XAR_SYSTEM_EXCEPTION:
            $template = "systemerror";
            break;
        case XAR_USER_EXCEPTION:
            $template = "usererror";
            break;
        case XAR_SYSTEM_MESSAGE:
            $template = "systeminfo";
            break;
        case XAR_NO_EXCEPTION:
            break;
        default:
            break;
    }

    $data = array();
    $data['major'] = $error->getMajor();
    $data['type'] = $error->getType();
    $data['title'] = $error->getTitle();
    $data['short'] = $error->getShort();
    $data['long'] = $error->getLong();
    $data['hint'] = $error->getHint();
    $data['stack'] = $error->getStack();
    $data['product'] = $error->getProduct();
    $data['component'] = $error->getComponent();

    if ($format == 'template') {
        $theme_dir = xarTplGetThemeDir();
        if(file_exists($theme_dir . '/modules/base/message-' . $error->id . '.xt')) {
            return xarTplFile($theme_dir . '/modules/base/message-' . $error->id . '.xt', $data);
        } elseif(file_exists($theme_dir . '/modules/base/message-' . $template . '.xt')) {
            return xarTplFile($theme_dir . '/modules/base/message-' . $template . '.xt', $data);
        } else {
            return xarTplFile('modules/base/xartemplates/message-' . $template . '.xd', $data);
        }
    }
    elseif ($format == 'rawhtml') {
        $msg = "<b><u>" . $data['title'] . "</u></b><br /><br />";
        $msg .= "<b>Description:</b> " . $data['short'] . "<br /><br />";
        $msg .= "<b>Explanation:</b> " . $data['long'] . "<br /><br/>";
        if ($data['hint'] != '') $msg .= "<b>Hint:</b> " . $data['hint'] . "<br /><br/>";
        if ($data['stack'] != '') $msg .= "<b>Stack:</b><br />" . $data['stack'] . "<br /><br />";
        if ($data['product'] != '') $msg .= "<b>Product:</b> " . $data['product'] . "<br /><br />";
        if ($data['component'] != '') $msg .= "<b>Component:</b> " . $data['component'] . "<br /><br />";
        return $msg;
    }
    elseif ($format == 'text') {
        $msg = $data['title'] . "\n";
        $msg .= "Description: " . $data['short'] . "\n";
        $msg .= "Explanation: " . $data['long'] . "\n";
        if ($data['hint'] != '') $msg .= "Hint: " . $data['hint'] . "\n";
        if ($data['stack'] != '') $msg .= "Stack:\n" . $data['stack'] . "\n";
        if ($data['product'] != '') $msg .= "Product: " . $data['product'] . "\n";
        if ($data['component'] != '') $msg .= "Component: " . $data['component'] . "\n";
        return $msg;
    }
}

/**
 * Gets a formatted array of errors
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param format string one of template or plain
 * @param stacktype string one of CORE or ERROR
 * @return array of formatted errors
 */
function xarErrorGet($stacktype = "ERROR",$format='data')
{
    $msgs = xarException__formatStack($format,$stacktype);
    $datamsgs = array();

    foreach($msgs as $msg) {
        $data['major'] = $msg->getMajor();
        $data['type'] = $msg->getType();
        $data['title'] = $msg->getTitle();
        $data['short'] = $msg->getShort();
        $data['long'] = $msg->getLong();
        $data['hint'] = $msg->getHint();
        $data['stack'] = $msg->getStack();
        $data['product'] = $msg->getProduct();
        $data['component'] = $msg->getComponent();
        $datamsgs[] = $data;
    }
    return $datamsgs;
}

// PRIVATE FUNCTIONS

/**
 * Adds formatting to the raw error messages
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access private
 * @param format string one of html or text
 * @return array of formatted error msgs
 */
function xarException__formatStack($format,$stacktype = "ERROR")
{
    global $ErrorStack;
    global $CoreStack;

    if ($stacktype == "ERROR") $stack = $ErrorStack;
    else $stack = $CoreStack;

    $formattedmsgs = array();
    while (!$stack->isempty()) {

        $error = $stack->pop();

        // FIXME: skip noexception because it's not rendered well
        if (empty($error->major)) continue;

        if ($format == 'template' || $format == 'rawhtml') {
            if (!class_exists('HTMLExceptionRendering')) {
                include_once(dirname(__FILE__) . "/exceptions/htmlexceptionrendering.class.php");
            }
            $msg = new HTMLExceptionRendering($error);
        }
        else {
            if (!class_exists('TextExceptionRendering')) {
                include_once(dirname(__FILE__) . "/exceptions/textexceptionrendering.class.php");
            }
            $msg = new TextExceptionRendering($error);
        }
        $formattedmsgs[] = $msg;
    }
    return $formattedmsgs;
}

/**
 * Error handlers section
 *
 * For several areas there are specific bridges to route errors into
 * the exception subsystem:
 *
 * Handlers:
 * 1. assert failures -> xarException__assertErrorHandler($script,$line,$code)
 * 2. ado db errors   -> xarException__dbErrorHandler($databaseName, $funcName, $errNo, $errMsg, $param1 = fail, $param2 = false)
 * 3. php Errors      -> xarException__phpErrorHandler($errorType, $errorString, $file, $line)
 *
 * @todo Use trigger_error functionality for them all and take php5 into account
 */

/**
 * Error handler for assert failures
 *
 * This handler is called when assertions in code fail.
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @access private
 * @param  string  $script filename in which assertion failed
 * @param  integer $line   linenumber on which assertion is made
 * @param  string  $code   the assertion expressed in code which evaluated to false
 * @return void
 */
function xarException__assertErrorHandler($script,$line,$code)
{
    // Redirect the assertion to a system exception
    $msg = "ASSERTION FAILED: $script [$line] : $code";
    xarErrorSet(XAR_SYSTEM_EXCEPTION,'ASSERT_FAILURE',$msg);
}

/**
 * ADODB error handler bridge
 *
 * @access private
 * @param  string databaseName
 * @param  string funcName
 * @param  integer errNo
 * @param  string errMsg
 * @param  bool param1
 * @param  bool param2
 * @raise  DATABASE_ERROR
 * @return void
 * @todo   <marco> complete it
 */
function xarException__dbErrorHandler($databaseName, $funcName, $errNo, $errMsg, $param1 = false, $param2 = false)
{
    if ($funcName == 'EXECUTE') {
        if (function_exists('xarML')) {
            $msg = xarML('Database error while executing: \'#(1)\'; error description is: \'#(2)\'.', $param1, $errMsg);
        } else {
            $msg = 'Database error while executing: '. $param1 .'; error description is: ' . $errMsg;
        }
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemException("ErrorNo: ".$errNo.", Message:".$msg));
    } else {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR', $errMsg);
    }
}

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
    $errLevel = xarCore_getSystemVar('Exception.ErrorLevel',true);
    if(!isset($errLevel)) $errLevel = E_ALL;
    if (!error_reporting() || !($errorType & $errLevel)) {
        // Log the message so it is not lost.
        // TODO: make this message available to calling functions that suppress
        // errors through '@'.
        $msg = "PHP error code $errorType at line $line of $file: $errorString";
        xarLogMessage($msg);
        return;
    }

    //Newer php versions have a 5th parameter that will give us back the context
    //The variable values during the error...
    $msg = "At: " . $file." (Line: " . $line.")\n". $errorString ;

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
    }

    // Make cached files also display their source file if it's a template
    // This is just for convenience when giving support, as people will probably
    // not look in the CACHEKEYS file to mention the template.
    if(isset($GLOBALS['xarTpl_cacheTemplates'])) {
        $sourcetmpl='';
        $base = basename(strval($file),'.php');
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
        if ($GLOBALS['xarRequest_allowShortURLs'] && isset($GLOBALS['xarRequest_shortURLVariables']['module'])) {
            $module = $GLOBALS['xarRequest_shortURLVariables']['module'];
        // Then check in $_GET
        } elseif (isset($_GET['module'])) {
            $module = $_GET['module'];
        // Try to fallback to $HTTP_GET_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARS']['module'])) {
            $module = $GLOBALS['HTTP_GET_VARS']['module'];
        // Nothing found, return void
        } else {
            $module = '';
        }
        $product = '';
        $component = '';
        if ($module != '') {
            // load relative to the current file (e.g. for shutdown functions)
            include(dirname(__FILE__) . "/exceptions/xarayacomponents.php");
            foreach ($core as $corecomponent) {
                if ($corecomponent['name'] == $module) {
                    $component = $corecomponent['fullname'];
                    $product = "App - Core";
                    break;
                }
            }
            if ($component != '') {
                foreach ($apps as $appscomponent) {
                    if ($appscomponent['name'] == $module) {
                        $component = $appscomponent['fullname'];
                        $product = "App - Modules";
                    }
                }
            }
        }
        // Fall-back in case it's too late to redirect
        if (headers_sent() == true) {
            $rawmsg = "Normal Xaraya error processing has stopped because of an error encountered. <br /><br />";
            $rawmsg .= "The last registered error message is: <br /><br />";
            $rawmsg .= "Product: " . $product . "<br />";
            $rawmsg .= "Component: " . $component . "<br />";
            $rawmsg .= "PHP Error code: " . $errorType . "<br /><br />";
            $rawmsg .= $msg;
            echo $rawmsg;
            return;
        }
        // CHECKME: <mrb> This introduces a dependency to 2 subsystems
        xarResponseRedirect(xarModURL('base','user','systemexit',
        array('code' => $errorType,
              'exception' => $msg,
              'product' => $product,
              'component' => $component)));
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
    $btFuncName = array();

    if (function_exists('xdebug_enable')) {
        xdebug_enable();
        $btFuncName = xarException__xdebugBackTrace();
    } elseif (function_exists('debug_backtrace')) {
        $btFuncName = debug_backtrace();
    }
    return $btFuncName;
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
        for($i = 0, $max = count($stack); $i < $max; $i++) {
            $stack[$i]['args'] = $stack[$i]['params'];
        }
    }
    return array_reverse($stack);
}


function xarCoreExceptionFree()
{
    global $CoreStack;
    $CoreStack->initialize();
}

function xarIsCoreException()
{
    global $CoreStack;
    return $CoreStack->size() > 1;
}

//NOT GPLed CODE: (Probably Public Domain? or PHP's?)
//Code from PHP's manual on function print_r
//So this can work for versions lower than php 4.3 http://br.php.net/function.print_r
//Code by ???? matt at crx4u dot com??? Not clear from the manual
function xarException__formatBacktrace ($vardump,$key=false,$level=0)
{
    if (version_compare("4.3.0", phpversion(), "<=")) return print_r($vardump, true);
    //else
    //Getting afraid some of the arrays might reference itself... Dont know what will happen
    if ($level == 16) return '';

    $tabsize = 4;

    //make layout
    $return .= str_repeat(' ', $tabsize*$level);
    if ($level != 0) $key = "[$key] =>";

    //look for objects
    if (is_object($vardump))
        $return .= "$key ".get_class($vardump)." ".$vardump."\n";
    else
        $return .= "$key $vardump\n";

     if (gettype($vardump) == 'object' || gettype($vardump) == 'array') {
        $level++;
        $return .= str_repeat(' ', $tabsize*$level);
        $return .= "(\n";

        if (gettype($vardump) == 'object')
            $vardump = (array) get_object_vars($vardump);

        foreach($vadump as $key => $value)
            $return .= xarException__formatBacktrace($value,$key,$level+1);

        $return .= str_repeat(' ', $tabsize*$level);
        $return .= ")\n";
        $level--;
    }

     //return everything
     return $return;
}

?>
