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
 * @author Marco Canini <m.canini@libero.it>
 */

/**
 * No exception
 */
define('XAR_NO_EXCEPTION', 0);
/**
 * User exception
 */
define('XAR_USER_EXCEPTION', 1);
/**
 * System exception
 */
define('XAR_SYSTEM_EXCEPTION', 2);

/* Xaraya System Exceptions */

include "includes/exceptions/exceptionstack.class.php";
global $ExceptionStack;
$ExceptionStack = new xarExceptionStack();

include "includes/exceptions/systemexception.class.php";
include "includes/exceptions/defaultuserexception.class.php";
include "includes/exceptions/noexception.class.php";
include "includes/exceptions/errorcollection.class.php";

/* Exception Handling System implementation */

/**
 * Initializes the Exception Handling System
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access protected
 * @return bool true
 */
function xarException_init($args, $whatElseIsGoingLoaded)
{
    // The check for xdebug_enable is not necessary here, we want the handler enabled on the flag, period.
    if ($args['enablePHPErrorHandler'] == true ) { // && !function_exists('xdebug_enable')) {
        set_error_handler('xarException__phpErrorHandler');
    }

    xarExceptionFree();
    return true;
}

/**
 * Allows the caller to raise an exception
 *
 * Valid value for $major paramter are: XAR_NO_EXCEPTION, XAR_USER_EXCEPTION, XAR_SYSTEM_EXCEPTION.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @param major integer exception major number
 * @param exceptionId string exception identifier
 * @param value mixed exception value
 * @return void
 */
function xarExceptionSet($major, $exceptionId, $value = NULL)
{
    global $ExceptionStack;
    if ($major != XAR_NO_EXCEPTION &&
        $major != XAR_USER_EXCEPTION &&
        $major != XAR_SYSTEM_EXCEPTION) {
            xarCore_die('xarExceptionSet: Invalid major value: ' . $major);
    }

    //Checks for a @ presence in the given line, should stop from setting Xaraya or DB errors
    if (!error_reporting()) {
        return;
    }

    $stack = xarException__backTrace();
    if (!is_object($value)) {
        // The exception passed in is just a msg or an identifier, try to construct
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
        } else {
            $obj = new NoException($value);
        }

    }
    else {
        $obj = $value;
    }

    // At this point we have a nice exception object
    // Now add whatever properties are still missing
    $obj->setID($exceptionId);
    $obj->setStack($stack);
    $obj->major = $major;

    // Stick the object on the exception stack
    $ExceptionStack->push($obj);

    // If the XARDBG_EXCEPTIONS flag is set we log every raised exception.
    // This can be useful in debugging since EHS is not so perfect as a native
    // EHS could be (read damned PHP language :).
    if (xarCoreIsDebugFlagSet(XARDBG_EXCEPTIONS)) {
        xarLogMessage('The following exception is logged because the XARDBG_EXCEPTIONS flag is set.');
    // TODO: remove again once xarLogException works
        xarLogMessage($obj->toString(), XARLOG_LEVEL_ERROR);
        //xarLogException();
    }
}

/**
 * Gets the major number of current exception
 *
 * Allows the caller to establish whether an exception was raised, and to get the major number of raised exception.
 * The major number XAR_NO_EXCEPTION identifies the state in which no exception was raised.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return integer the major value of raised exception
 */
function xarExceptionMajor()
{
    global $ExceptionStack;
    if ($ExceptionStack->isempty()) return '';
    $exp = $ExceptionStack->peek();
    return $exp->getMajor();
}

/**
 * Gets the identifier of current exception
 *
 * Returns the exception identifier corresponding to the current exception.
 * If invoked when no exception was raised, a void value is returned.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return string the exception identifier
 */
function xarExceptionId()
{
    global $ExceptionStack;
    if ($ExceptionStack->isempty()) return '';
    $exp = $ExceptionStack->peek();
    return $exp->getID();
}

/**
 * Gets the value of current exception
 *
 * Returns the value corresponding to the current exception.
 * If invoked when no exception or an exception for which there is no associated information was raised, a void value is returned.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return mixed exception value
 */
function xarExceptionValue()
{
    global $ExceptionStack;
    if ($ExceptionStack->isempty()) return '';
    return $ExceptionStack->peek();
}

/**
 * Resets current exception status
 *
 * xarExceptionFree is a shortcut for xarExceptionSet(XAR_NO_EXCEPTION, NULL, NULL).
 * You must always call this function when you handle a caught exception or
 * equivalently you don't throw the exception back to the caller.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return void
 */
function xarExceptionFree()
{
    global $ExceptionStack;
    $ExceptionStack->initialize();
}

/**
 * Handles the current exception
 *
 * You must always call this function when you handle a caught exception.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return void
 */
function xarExceptionHandled()
{
//    if (xarExceptionMajor() == XAR_NO_EXCEPTION) {
//            xarCore_die('xarExceptionHandled: Invalid major value: XAR_NO_EXCEPTION');
//    }

    global $ExceptionStack;
    $ExceptionStack->pop();
}

/**
 * Renders the current exception
 *
 * Returns a string formatted according to the $format parameter that provides all the information
 * available on current exception.
 * If there is no exception currently raised an empty string is returned.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @param format string one of html or text
 * @return string the string representing the raised exception
 */
function xarExceptionRender($format)
{

    global $ExceptionStack;

    while (!$ExceptionStack->isempty()) {
//    if ($ExceptionStack->size() == 1) {
//        $exception = $ExceptionStack->peek();
//        echo $exception->getTitle();
//        exit;
//    }
        $exception = $ExceptionStack->pop();

        switch ($exception->getMajor()) {
            case XAR_SYSTEM_EXCEPTION:
                $type = 'System Error';
                $template = "system";
                break;
            case XAR_USER_EXCEPTION:
                $type = 'User Error';
                $template = "user";
                break;
            case XAR_NO_EXCEPTION:
                continue 2;
            default:
                continue 2;
        }

        if ($format == 'html') {
            include_once "includes/exceptions/htmlexceptionrendering.class.php";
            $rendering = new HTMLExceptionRendering($exception);
        }
        else {
            include_once "includes/exceptions/textexceptionrendering.class.php";
            $rendering = new TextExceptionRendering($exception);
        }

        $data = array();
        $data['type'] = $type;
        $data['title'] = $rendering->getTitle();
        $data['short'] = $rendering->getShort();
        $data['long'] = $rendering->getLong();
        $data['hint'] = $rendering->getHint();
        $data['stack'] = $rendering->getStack();
//        echo $ExceptionStack->size();exit;
    }
   return  xarTplModule('base',$template, 'exception', $data);
}

// PRIVATE FUNCTIONS

/**
 * PHP error handler bridge to Xaraya exceptions
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access private
 * @return void
 */
function xarException__phpErrorHandler($errorType, $errorString, $file, $line)
{
    global $ExceptionStack;

    //Newer php versions have a 5th parameter that will give us back the context
    //The variable values during the error...

    // Make cached files also display their source file if it's a template
    // This is just for convenience when giving support, as people will probably
    // not look in the CACHEKEYS file to mention the template.
    if(isset($GLOBALS['xarTpl_cacheTemplates'])) {
        $sourcetmpl='';
        $base = basename($file,'.php');
        $varDir = xarCoreGetVarDirPath();
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

    switch($errorType) {
        case 2: // Warning
        case 8: // Notice
            $msg = $file.'('.$line."):\n". $errorString ;
            if(isset($sourcetmpl)) $msg .= "\n[".$sourcetmpl."]";

            if($ExceptionStack->isempty()) {
                $ExceptionStack->initialize();
            }
            else {
                $exception = $ExceptionStack->peek();
                if ($exception->getMajor() != XAR_NO_EXCEPTION) {
                    $id = xarExceptionId();
                    $value = xarExceptionValue();
                    if ($exception->getID() == 'ERROR_COLLECTION') {
                        // add an exception to error collection
                        $thisexcp = new SystemException($msg);
                        $thisexcp->setID('PHP_ERROR');
                        $exception->exceptions[] = $thisexcp;
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ErrorCollection', $value);
                    } else {
                        // raise an error collection
                        $exc = new ErrorCollection();
                        $thisexcp = $exception;
                        $exc->exceptions[] = $thisexcp;
                        $thisexcp = new SystemException($msg);
                        $thisexcp->setID('PHP_ERROR');
                        $exception->exceptions[] = $thisexcp;
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ErrorCollection', $exc);
                    }
                } else {
                    // raise an error collection
//                    echo $ExceptionStack->size();
                    $exc = new ErrorCollection();
                    $thisexcp = new SystemException($msg);
                    $thisexcp->setID('PHP_ERROR');
                    $exception->exceptions[] = $thisexcp;
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ErrorCollection', $exc);
                }
            }
            break;
        default:
            echo "<b>FATAL</b> $errorString<br />\n";
            echo "Fatal error in $file at line $line<br />\n";
            exit;
    }

    // This will make us log the errors, still not break the script
    //if they are not supposed to
    if (!(error_reporting() & $errorType)) xarExceptionHandled();
}

/**
 * Returns a debug back trace
 *
 * @author Marco Canini <m.canini@libero.it>
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
 * @author Marco Canini <m.canini@libero.it>
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

?>