<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Exception Handling System
// ----------------------------------------------------------------------

// NOTE: Single threaded policy guarantees that this stuff will work
//       This implementation is NOT thread safe.

define('XAR_NO_EXCEPTION', 0);
define('XAR_USER_EXCEPTION', 1);
define('XAR_SYSTEM_EXCEPTION', 2);

/* PostNuke System Exceptions */

// SystemException class

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
// class ID_NOT_EXIST extends SystemException {}
// class BAD_PARAM extends SystemException {}
// class DATABASE_ERROR extends SystemException {}
// class NO_PERMISSION extends SystemException {}
// class MODULE_NOT_EXIST extends SystemException {}
// class MODULE_FILE_NOT_EXIST extends SystemException {}
// class MODULE_FUNCTION_NOT_EXIST extends SystemException {}
// class MODULE_NOT_ACTIVE extends SystemException {}
// class NOT_LOGGED_IN extends SystemException {}
// class NOT_IMPLEMENTED extends SystemException {}
// class DEPRECATED_API extends SystemException {}
// class VARIABLE_NOT_REGISTERED extends SystemException {}
// class PHP_ERROR extends SystemException {}

class DefaultUserException
{
    var $msg;

    function DefaultUserException($msg)
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

// ErrorCollection exception
// it has to be raised as user exception
// it's a container of error/exceptions
// for now it's used only by the PHP error handler bridge

class ErrorCollection
{
    var $exceptions = array();

    function toString()
    {
        $text = "ErrorCollection exception\n";
        foreach($this->exceptions as $exc) {
            $text .= "Exception $exc[id]\n";
            if (method_exists($exc['value'], 'toString')) {
                $text .= $exc['value']->toString();
                $text .= "\n";
            }
        }
        return $text;
    }

    function toHTML()
    {
        $text = 'ErrorCollection exception<br />';
        foreach($this->exceptions as $exc) {
            $text .= "Exception identifier: <b>$exc[id]</b><br />";
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
 * Initialise the Exception Handling System
 */
function xarException_init($args)
{
    global $xarException_useXDebug;
    if (function_exists('xdebug_enable')) {
        xdebug_enable();
        $xarException_useXDebug = true;
    } else {
        $xarException_useXDebug = false;
        if ($args['enablePHPErrorHandler'] == true) {
            set_error_handler('xarException__phpErrorHandler');
        }
    }
    xarExceptionFree();
    return true;
}

/**
 * allow a function to raise an exception
 * The caller must supply a value for the major parameter.
 * @param major can have one of the values XAR_NO_EXCEPTION, XAR_USER_EXCEPTION, or XAR_SYSTEM_EXCEPTION
 * @param exceptionId identifier representing the exception type
 * @param value PHP class containing exception value
 * @returns void
 */
function xarExceptionSet($major, $exceptionId, $value = NULL)
{
    global $xarException_major, $xarException_exceptionId;
    global $xarException_value, $xarException_useXDebug;

    if ($major != XAR_NO_EXCEPTION &&
        $major != XAR_USER_EXCEPTION &&
        $major != XAR_SYSTEM_EXCEPTION) {
            die('xarExceptionSet: Invalid major value: ' . $major);
    }

    if ($xarException_useXDebug) {
        $stack = xdebug_get_function_stack();
    }

    if (!is_object($value)) {
        if ($major == XAR_SYSTEM_EXCEPTION) {
            $value = new SystemException('No further information available.');
        } elseif ($major == XAR_USER_EXCEPTION) {
            $value = new DefaultUserException('No further information available.');
        }
    }

    if ($xarException_useXDebug) {
        $value->__stack = array_reverse($stack);
    }

    // Set new status
    $xarException_major = $major;
    $xarException_exceptionId = $exceptionId;
    $xarException_value = $value;

    // If the XARDBG_EXCEPTIONS flag is set we log every raised exception.
    // This can be useful in debugging since EHS is not so perfect as a native
    // EHS could be (read damned PHP language :).
    if (xarCoreIsDebugFlagSet(XARDBG_EXCEPTIONS)) {
        xarLogMessage('The following exception is logged because the XARDBG_EXCEPTIONS flag is set.');
        xarLogException();
    }
}

/**
 * allow the caller to establish whether an exception was raised, and to get the type of raised exception
 * @returns integer
 * @return the major value of raised exception, XAR_NO_EXCEPTION identifies the state in wich no exception was raised
 */
function xarExceptionMajor()
{
    global $xarException_major;
    return $xarException_major;
}

/**
 * get exception ID
 * @returns string
 * @return the string identifying the exception, if invoked when no exception was raised a void value is returned
 */
function xarExceptionId()
{
    global $xarException_exceptionId;
    return $xarException_exceptionId;
}

/**
 * get exception value
 * @returns object
 * @return an object corresponding to this exception, if invoked when no exception or an exception for which there is no associated information was raised, a void value is returned
 */
function xarExceptionValue()
{
    global $xarException_value;
    return $xarException_value;
}

/**
 * reset current exception status, it's a shortcut for xarExceptionSet(XAR_NO_EXCEPTION, NULL, NULL)
 * @note you must always call this function when you handle a catched exception or equivalently you don't throw the exception back to the caller
 * @returns void
 */
function xarExceptionFree()
{
    global $xarException_major, $xarException_exceptionId, $xarException_value;
    $xarException_major = XAR_NO_EXCEPTION;
    $xarException_exceptionId = NULL;
    $xarException_value = NULL;
}

/**
 * Renders the current raised exception as a string formatted according to the format parameter
 * @param format one of html or text
 * @returns string
 * @return the string representing the raised exception or an empty string if the exception status
 * is XAR_NO_EXCEPTION
 */
function xarExceptionRender($format)
{
    global $xarException_major, $xarException_exceptionId;
    global $xarException_value, $xarException_useXDebug;

    switch ($xarException_major) {
        case XAR_SYSTEM_EXCEPTION:
            $type = 'SYSTEM Exception';
            break;
        case XAR_USER_EXCEPTION:
            $type = 'USER Exception';
            break;
        default:
            return '';
    }
    if ($format == 'html') {
        $text = '<font color="purple">('.$type.')</font> <b>'.$xarException_exceptionId.'</b>:<br />';
        if (method_exists($xarException_value, 'toHTML')) {
            $text .= '<font color="red">'.$xarException_value->toHTML().'</font>';
        }
        if ($xarException_useXDebug) {
            $stack = $xarException_value->__stack;
            for ($i = 1, $j = 0; $i < count($stack); $i++, $j++) {
                $text .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;at '.$stack[$i]['function'].'(';
                $file = basename($stack[$j]['file']);
                $text .= $file.':';
                $text .= $stack[$j]['line'].')<br />';
            }
        }
    } else /*if ($format == 'text')*/ {
        $text = '('.$type.') '.$xarException_exceptionId.":\n";
        if (method_exists($xarException_value, 'toString')) {
            $text .= $xarException_value->toString();
        }
        if ($xarException_useXDebug) {
            $stack = $xarException_value->__stack;
            for ($i = 1, $j = 0; $i < count($stack); $i++, $j++) {
                $text .= '     at '.$stack[$i]['function'].'(';
                $file = basename($stack[$j]['file']);
                $text .= $file.':';
                $text .= $stack[$j]['line'].")\n";
            }
        }
    }

    return $text;
}

// PRIVATE FUNCTIONS

/**
 * PHP error handler bridge to PostNuke exceptions
 */
function xarException__phpErrorHandler($errorType, $errorString, $file, $line)
{
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
                    xarExceptionSet(XAR_USER_EXCEPTION, 'ErrorCollection', $value);
                } else {
                    // raise an error collection
                    $exc = new ErrorCollection();
                    $exc->exceptions[] = array('id' => $id,
                                               'value' => $value);
                    $exc->exceptions[] = array('id' => 'PHP_ERROR', 
                                               'value' => new SystemException($msg));
                    xarExceptionSet(XAR_USER_EXCEPTION, 'ErrorCollection', $exc);
                }
            } else {
                // raise an error collection
                $exc = new ErrorCollection();
                $exc->exceptions[] = array('id' => 'PHP_ERROR', 
                                           'value' => new SystemException($msg));
                xarExceptionSet(XAR_USER_EXCEPTION, 'ErrorCollection', $exc);
            }
            break;
        default:
            echo "<b>FATAL</b> $errorString<br />\n";
            echo "Fatal error in $file at line $line<br />\n";
            exit;
    }
}

?>
