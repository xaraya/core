<?php
// File: $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2001 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Exception Handling System
// ----------------------------------------------------------------------

// NOTE: Single threaded policy guarantees that this stuff will work
//       This implementation is NOT thread safe.

define('PN_NO_EXCEPTION', 0);
define('PN_USER_EXCEPTION', 1);
define('PN_SYSTEM_EXCEPTION', 2);

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
        return nl2br(pnVarPrepForDisplay($this->msg)) . '<br/>';
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
        return nl2br(pnVarPrepForDisplay($this->msg)) . '<br/>';
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
function pnException_init($args)
{
    global $pnException_useXDebug;
    if (function_exists('xdebug_enable')) {
        xdebug_enable();
        $pnException_useXDebug = true;
    } else {
        $pnException_useXDebug = false;
        if ($args['enablePHPErrorHandler'] == true) {
            set_error_handler('pnException__phpErrorHandler');
        }
    }
    pnExceptionFree();
    return true;
}

/**
 * allow a function to raise an exception
 * The caller must supply a value for the major parameter.
 * @param major can have one of the values PN_NO_EXCEPTION, PN_USER_EXCEPTION, or PN_SYSTEM_EXCEPTION
 * @param exceptionId identifier representing the exception type
 * @param value PHP class containing exception value
 * @returns void
 */
function pnExceptionSet($major, $exceptionId, $value = NULL)
{
    global $pnException_major, $pnException_exceptionId;
    global $pnException_value, $pnException_useXDebug;

    if ($major != PN_NO_EXCEPTION &&
        $major != PN_USER_EXCEPTION &&
        $major != PN_SYSTEM_EXCEPTION) {
            die('pnExceptionSet: Invalid major value: ' . $major);
    }

    if ($pnException_useXDebug) {
        $stack = xdebug_get_function_stack();
    }

    if (!is_object($value)) {
        if ($major == PN_SYSTEM_EXCEPTION) {
            $value = new SystemException('No further information available.');
        } elseif ($major == PN_USER_EXCEPTION) {
            $value = new DefaultUserException('No further information available.');
        }
    }

    if ($pnException_useXDebug) {
        $value->__stack = array_reverse($stack);
    }

    // Set new status
    $pnException_major = $major;
    $pnException_exceptionId = $exceptionId;
    $pnException_value = $value;

    // If the PNDBG_EXCEPTIONS flag is set we log every raised exception.
    // This can be useful in debugging since EHS is not so perfect as a native
    // EHS could be (read damned PHP language :).
    if (pnCoreIsDebugFlagSet(PNDBG_EXCEPTIONS)) {
        pnLogMessage('The following exception is logged because the PNDBG_EXCEPTIONS flag is set.');
        pnLogException();
    }
}

/**
 * allow the caller to establish whether an exception was raised, and to get the type of raised exception
 * @returns integer
 * @return the major value of raised exception, PN_NO_EXCEPTION identifies the state in wich no exception was raised
 */
function pnExceptionMajor()
{
    global $pnException_major;
    return $pnException_major;
}

/**
 * get exception ID
 * @returns string
 * @return the string identifying the exception, if invoked when no exception was raised a void value is returned
 */
function pnExceptionId()
{
    global $pnException_exceptionId;
    return $pnException_exceptionId;
}

/**
 * get exception value
 * @returns object
 * @return an object corresponding to this exception, if invoked when no exception or an exception for which there is no associated information was raised, a void value is returned
 */
function pnExceptionValue()
{
    global $pnException_value;
    return $pnException_value;
}

/**
 * reset current exception status, it's a shortcut for pnExceptionSet(PN_NO_EXCEPTION, NULL, NULL)
 * @note you must always call this function when you handle a catched exception or equivalently you don't throw the exception back to the caller
 * @returns void
 */
function pnExceptionFree()
{
    global $pnException_major, $pnException_exceptionId, $pnException_value;
    $pnException_major = PN_NO_EXCEPTION;
    $pnException_exceptionId = NULL;
    $pnException_value = NULL;
}

/**
 * Renders the current raised exception as a string formatted according to the format parameter
 * @param format one of html or text
 * @returns string
 * @return the string representing the raised exception or an empty string if the exception status
 * is PN_NO_EXCEPTION
 */
function pnExceptionRender($format)
{
    global $pnException_major, $pnException_exceptionId;
    global $pnException_value, $pnException_useXDebug;

    switch ($pnException_major) {
        case PN_SYSTEM_EXCEPTION:
            $type = 'SYSTEM Exception';
            break;
        case PN_USER_EXCEPTION:
            $type = 'USER Exception';
            break;
        default:
            return '';
    }
    if ($format == 'html') {
        $text = '<font color="purple">('.$type.')</font> <b>'.$pnException_exceptionId.'</b>:<br />';
        if (method_exists($pnException_value, 'toHTML')) {
            $text .= '<font color="red">'.$pnException_value->toHTML().'</font>';
        }
        if ($pnException_useXDebug) {
            $stack = $pnException_value->__stack;
            for ($i = 1, $j = 0; $i < count($stack); $i++, $j++) {
                $text .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;at '.$stack[$i]['function'].'(';
                $file = basename($stack[$j]['file']);
                $text .= $file.':';
                $text .= $stack[$j]['line'].')<br />';
            }
        }
    } else /*if ($format == 'text')*/ {
        $text = '('.$type.') '.$pnException_exceptionId.":\n";
        if (method_exists($pnException_value, 'toString')) {
            $text .= $pnException_value->toString();
        }
        if ($pnException_useXDebug) {
            $stack = $pnException_value->__stack;
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
function pnException__phpErrorHandler($errorType, $errorString, $file, $line)
{
    switch($errorType) {
        case 2: // Warning
        case 8: // Notice
            $msg = $file.'('.$line."):\n".$errorString;
            if (pnExceptionMajor() != PN_NO_EXCEPTION) {
                $id = pnExceptionId();
                $value = pnExceptionValue();
                if ($id == 'ERROR_COLLECTION') {
                    // add an exception to error collection
                    $value->exceptions[] = array('id' => 'PHP_ERROR', 
                                                 'value' => new SystemException($msg));
                    pnExceptionSet(PN_USER_EXCEPTION, 'ErrorCollection', $value);
                } else {
                    // raise an error collection
                    $exc = new ErrorCollection();
                    $exc->exceptions[] = array('id' => $id,
                                               'value' => $value);
                    $exc->exceptions[] = array('id' => 'PHP_ERROR', 
                                               'value' => new SystemException($msg));
                    pnExceptionSet(PN_USER_EXCEPTION, 'ErrorCollection', $exc);
                }
            } else {
                // raise an error collection
                $exc = new ErrorCollection();
                $exc->exceptions[] = array('id' => 'PHP_ERROR', 
                                           'value' => new SystemException($msg));
                pnExceptionSet(PN_USER_EXCEPTION, 'ErrorCollection', $exc);
            }
            break;
        default:
            echo "<b>FATAL</b> $errorString<br />\n";
            echo "Fatal error in $file at line $line<br />\n";
            exit;
    }
}

?>
