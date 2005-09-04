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

/**
 * SystemException
 *
 * @package exceptions
 */
class SystemException
{
    var $msg;

    function SystemException($msg)
    {
        $this->msg = $msg;
    }

    function toString()
    {
        return $this->msg;
    }

    function toHTML()
    {
        return nl2br(xarVarPrepForDisplay($this->msg)) . '<br/>';
    }

}

// We should inherit from it all concrete system exceptions, but for
// performance we only use SystemException class and provide
// well known exception IDs

// Exception IDs

// class UNKNOWN extends SystemException {}
// {ML_add_key 'UNKNOWN'}
// class ID_NOT_EXIST extends SystemException {}
// {ML_add_key 'ID_NOT_EXIST'}
// class BAD_PARAM extends SystemException {}
// {ML_add_key 'BAD_PARAM'}
// class EMPTY_PARAM extends SystemException {}
// {ML_add_key 'EMPTY_PARAM'}
// class DATABASE_ERROR extends SystemException {}
// {ML_add_key 'DATABASE_ERROR'}
// class DATABASE_ERROR_QUERY extends SystemException {}
// {ML_add_key 'DATABASE_ERROR_QUERY'}
// class NO_PERMISSION extends SystemException {}
// {ML_add_key 'NO_PERMISSION'}
// class MODULE_NOT_EXIST extends SystemException {}
// {ML_add_key 'MODULE_NOT_EXIST'}
// class MODULE_FILE_NOT_EXIST extends SystemException {}
// {ML_add_key 'MODULE_FILE_NOT_EXIST'}
// class MODULE_FUNCTION_NOT_EXIST extends SystemException {}
// {ML_add_key 'MODULE_FUNCTION_NOT_EXIST'}
// class MODULE_NOT_ACTIVE extends SystemException {}
// {ML_add_key 'MODULE_NOT_ACTIVE'}
// class NOT_LOGGED_IN extends SystemException {}
// {ML_add_key 'NOT_LOGGED_IN'}
// class NOT_IMPLEMENTED extends SystemException {}
// {ML_add_key 'NOT_IMPLEMENTED'}
// class DEPRECATED_API extends SystemException {}
// {ML_add_key 'DEPRECATED_API'}
// class VARIABLE_NOT_REGISTERED extends SystemException {}
// {ML_add_key 'VARIABLE_NOT_REGISTERED'}
// class EVENT_NOT_REGISTERED extends SystemException {}
// {ML_add_key 'EVENT_NOT_REGISTERED'}
// class LOCALE_NOT_AVAILABLE extends SystemException {}
// {ML_add_key 'LOCALE_NOT_AVAILABLE'}
// class LOCALE_NOT_EXIST extends SystemException {}
// {ML_add_key 'LOCALE_NOT_EXIST'}
// class CONTEXT_NOT_EXIST extends SystemException {}
// class TEMPLATE_NOT_EXIST extends SystemException {}
// {ML_add_key 'TEMPLATE_NOT_EXIST'}
// class PHP_ERROR extends SystemException {}

/**
 *
 *
 * @package exceptions
 */
class DefaultUserException
{
    var $msg;
    var $link;

    function DefaultUserException($msg, $link = NULL)
    {
        $this->msg = $msg;
        $this->link = $link;
    }

    function toString()
    {
        return $this->msg;
    }

    function toHTML()
    {
        $str = "<pre>\n" . xarVarPrepForDisplay($this->msg) . "\n</pre><br/>";
        if ($this->link) {
            $str .= '<a href="'.$this->link[1].'">'.$this->link[0].'</a><br/>';
        }
        return $str;
    }

}

/**
 * ErrorCollection
 *
 * it has to be raised as user exception
 * it's a container of error/exceptions
 * for now it's used only by the PHP error handler bridge
 * @package exceptions
 */
class ErrorCollection
{
    var $exceptions = array();

    function toString()
    {
        $text = "";
        foreach($this->exceptions as $exc) {
//            $text .= "Exception $exc[id]\n";
            if (method_exists($exc['value'], 'toString')) {
                $text .= $exc['value']->toString();
                $text .= "\n";
            }
        }
        return $text;
    }

    function toHTML()
    {
        $text = "";
        foreach($this->exceptions as $exc) {
//            $text .= "Exception identifier: <b>$exc[id]</b><br />";
            if (method_exists($exc['value'], 'toHTML')) {
                $text .= $exc['value']->toHTML();
                $text .= '<br />';
            }
        }
        return $text;
    }

}

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
    if ($major != XAR_NO_EXCEPTION &&
        $major != XAR_USER_EXCEPTION &&
        $major != XAR_SYSTEM_EXCEPTION) {
            xarCore_die('xarExceptionSet: Invalid major value: ' . $major);
    }

    $stack = xarException__backTrace();
    if (!is_object($value)) {
        // The exception passed in is just a msg or an identifier, try to construct
        // the object here.
        if (is_string($value)) {
            // A msg was passed in, use that
            $value = xarML($value); // possibly redundant
        } else {
            if ($major == XAR_SYSTEM_EXCEPTION) {
                $value = xarMLByKey($exceptionId);
            } else {
                $value = xarML("No further information available.");
            }
        }
        
        if ($major == XAR_SYSTEM_EXCEPTION) {
            $value = new SystemException($value);
        } else {
            // FIXME: is defaulting to user exception the right thing?
            $value = new DefaultUserException($value);
        }
    }
    // value is now the appropriate exception object

    // Set new status
    $GLOBALS['xarException_stack'][] = array ('major' => $major,
                                              'exceptionId' => $exceptionId,
                                              'value' => $value,
                                              'stack' => $stack);

    // If the XARDBG_EXCEPTIONS flag is set we log every raised exception.
    // This can be useful in debugging since EHS is not so perfect as a native
    // EHS could be (read damned PHP language :).
    if (xarCoreIsDebugFlagSet(XARDBG_EXCEPTIONS)) {
        xarLogMessage('The following exception is logged because the XARDBG_EXCEPTIONS flag is set.');
    // TODO: remove again once xarLogException works
        xarLogMessage($value->toString(), XARLOG_LEVEL_ERROR);
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
    return $GLOBALS['xarException_stack'][count($GLOBALS['xarException_stack'])-1]['major'];
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
    return $GLOBALS['xarException_stack'][count($GLOBALS['xarException_stack'])-1]['exceptionId'];
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
    return $GLOBALS['xarException_stack'][count($GLOBALS['xarException_stack'])-1]['value'];
}

/**
 * Resets current exception status
 *
 * xarExceptionFree is a shortcut for xarExceptionSet(XAR_NO_EXCEPTION, NULL, NULL).
 * You must always call this function when you handle a catched exception or
 * equivalently you don't throw the exception back to the caller.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return void
 */
function xarExceptionFree()
{
    $GLOBALS['xarException_stack'] = array ();
    $GLOBALS['xarException_stack'][] = array ('major' => XAR_NO_EXCEPTION, 'exceptionId' => NULL, 'value' => NULL);
}

/**
 * Handles the current exception
 *
 * You must always call this function when you handled a caught exception.
 *
 * @author Marco Canini <m.canini@libero.it>
 * @access public
 * @return void
 */
function xarExceptionHandled()
{
    if (xarExceptionMajor() == XAR_NO_EXCEPTION) {
            xarCore_die('xarExceptionHandled: Invalid major value: XAR_NO_EXCEPTION');
    }

    array_pop($GLOBALS['xarException_stack']);
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
    $text = '';

//    echo count($GLOBALS['xarException_stack']);exit;
    foreach($GLOBALS['xarException_stack'] as $exception) {

        $data['id'] = $exception['id'];

        if ($format == 'html') {
            if (method_exists($exception['value'], 'toHTML')) {
                $data['message'] = $exception['value']->toHTML();
            }
        } else {
            if (method_exists($exception['value'], 'toString')) {
                $data['message'] = $exception['value']->toString();
            }
        }

        switch ($exception['major']) {
            case XAR_SYSTEM_EXCEPTION:
                $type = 'System Error';
                $template = "system";
                $data['type'] = $type;
                break;
            case XAR_USER_EXCEPTION:
                $type = 'User Error';
                $template = "user";
                $data['type'] = $type;

/*                if ($format == 'html') {
                    $text .= '<br /><span style="color: purple">'.$type.'</span><br /><br />';
                    if (method_exists($exception['value'], 'toHTML')) {
                        $text .= '<span style="color: red">'.$exception['value']->toHTML().'</span>';
                    }
                } else {
                    $text .= $type.": \n";
                    if (method_exists($exception['value'], 'toString')) {
                        $text .= $exception['value']->toString()."\n";
                    }
                }
*/
                continue 2;
            default:
                continue 2;
        }


        $showParams = xarCoreIsDebugFlagSet(XARDBG_SHOW_PARAMS_IN_BT);

        //This format thing should be dealt some other way...
        // BL? depending on output type...
        if ($format == 'html') {
            $text .= '<span style="color: purple">('.$type.')</span> <b>'.$exception['exceptionId'].'</b>:<br />';
            if (method_exists($exception['value'], 'toHTML')) {
                $text .= '<span style="color: red">'.$exception['value']->toHTML().'</span>';
            }

            if ($exception['major'] != XAR_USER_EXCEPTION) {
                $stack = $exception['stack'];
                for ($i = 2, $j = 1; $i < count($stack); $i++, $j++) {
                    if (isset($stack[$i]['function'])) $function = $stack[$i]['function'];
                    else $function = '{}';
                    $text .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;at '.$function.'(';
                    // Note: eval() doesn't generate file or line
                    if (isset($stack[$j]['file'])) $text .= basename($stack[$j]['file']).':';
                    if (isset($stack[$j]['line'])) $text .= $stack[$j]['line'];
                    $text .= ')<br />';
                    if ($showParams && isset($stack[$i]['args']) && is_array($stack[$i]['args']) && count($stack[$i]['args']) > 0) {
                        ob_start();
                        print_r($stack[$i]['args']);
                        $dump = ob_get_contents();
                        ob_end_clean();
                        $text .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . htmlspecialchars($dump);
                        $text .= '<br />';
                    }
                }
            }
        } else {
            $text .= '('.$type.') '.$exception['exceptionId'].":\n";
            if (method_exists($exception['value'], 'toString')) {
                $text .= $exception['value']->toString();
            }
            if ($exception['major'] != XAR_USER_EXCEPTION) {
                $stack = $exception['stack'];
                for ($i = 2, $j = 1; $i < count($stack); $i++, $j++) {
                    if (isset($stack[$i]['function'])) $function = $stack[$i]['function'];
                    else $function = '{}';
                    $text .= '     at '.$function.'(';
                    // Note: eval() doesn't generate file or line
                    if (isset($stack[$j]['file'])) $text .= basename($stack[$j]['file']).':';
                    if (isset($stack[$j]['line'])) $text .= $stack[$j]['line'];
                    $text .= ")\n";
                    if ($showParams && isset($stack[$i]['args']) && is_array($stack[$i]['args']) && count($stack[$i]['args']) > 0) {
                        ob_start();
                        print_r($stack[$i]['args']);
                        $dump = ob_get_contents();
                        ob_end_clean();
                        $text .= $dump;
                        $text .= "\n";
                    }
                }
            }
        }
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
    //Newer php versions have a 5th parameter that will give us back the context
    //The variable values during the error...
    switch($errorType) {
        case 2: // Warning
        case 8: // Notice
            $msg = $file.'('.$line."):\n".$errorString;
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                $id = xarExceptionId();
                $value = xarExceptionValue();
                if ($id == 'ERROR_COLLECTION') {
                    // add an exception to error collection
                    $value->exceptions[] = array('id' => 'PHP_ERROR',
                                                 'value' => new SystemException($msg));
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ErrorCollection', $value);
                } else {
                    // raise an error collection
                    $exc = new ErrorCollection();
                    $exc->exceptions[] = array('id' => $id,
                                               'value' => $value);
                    $exc->exceptions[] = array('id' => 'PHP_ERROR',
                                               'value' => new SystemException($msg));
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ErrorCollection', $exc);
                }
            } else {
                // raise an error collection
                $exc = new ErrorCollection();
                $exc->exceptions[] = array('id' => 'PHP_ERROR',
                                           'value' => new SystemException($msg));
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ErrorCollection', $exc);
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
