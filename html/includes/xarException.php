<?php
/**
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

/*
   For all documentation about exceptions see RFC-0054
*/
/*
   NOTE: I'm putting stuff on this all in this file now, we can split things up
         later on

   Q: do we need compatability classes for the legacy classes?
   Q: the exception handler receives the instantiated Exception class.
      How do we know there what is available in the derived object so we can
      specialize handling? To only allow deriving from XARExceptions and
      standardize there is probably not enough, but lets do that for now.

*/

/*
 * Error constants for exception throwing
 *
 * @todo probably move this to core loader or get rid of it completely, doesnt do something sane.
 */
define('E_XAR_ASSERT', 1);
define('E_XAR_PHPERR', 2);

/**
 * Public error types
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

// We still need the old classes
include "includes/exceptions/systemexception.class.php";
include "includes/exceptions/defaultuserexception.class.php";
include "includes/exceptions/systemmessage.class.php";

// Include the set of exception types
include "includes/exceptions/types.php";
// And the handlers to deal with them
include "includes/exceptions/handlers.php";

/**
 * Exceptions for the exception subsystem itself
 *
 */
// Special exception signalling the old ErrorSet was used
class ErrorDeprecationException extends DeprecationExceptions
{
    protected $message ="This exception was called through a deprecated API (usually xarErrorSet).\n Original error: #(1)";
    protected $hint    ="You should not use xarErrorSet anymore, but raise/catch real exceptions.\n Replace the 'xarErrorSet()' in the code with a try/catch block or delete it if the exception can be caught automatically.";
}

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
    // Send all exceptions to the default exception handler, no excuses
    set_exception_handler(array('ExceptionHandlers','defaulthandler'));

    // Send all error the the default error handler (which basically just throws a specific exception)
    set_error_handler(array('ExceptionHandlers','phperrors'));

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarError__shutdown_handler');
    return true;
}

/**
 * Debug function
 *
 * @return void
 * @throws DebugException
 * @author Marcel van der Boom
 **/
function debug($anything)
{
    throw new DebugException('DEBUGGING',var_export($anything,true));
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
 * Valid value for $major parameter are: XAR_NO_EXCEPTION, XAR_USER_EXCEPTION, XAR_SYSTEM_EXCEPTION, XAR_SYSTEM_MESSAGE.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param major integer error major number
 * @param errorID string error identifier
 * @param value error object
 * @throws ErrorDeprecationException
 * @return void
 */
function xarErrorSet($major, $errorID, $value = NULL)
{
    // MINIMAL backward compatability

    // If $value is a descendant from the old xarException class, get the message from it
    if($value instanceof xarException) {
        $msg = $value->toString();
    } else {
        // Probably already a string, use it.
        $msg = $value;
    }
    if($msg=='') $msg = 'No information supplied';
    // TODO: we should map errorID to an exception class to be a little friendlier
    // Raise a special exception, pointing people to not use this ErrorSet anymore.
    throw new ErrorDeprecationException(array($msg,$major));
}

/**
 * Gets the major number of current error
 *
 * Allows the caller to establish whether an error was raised, and to get the major number of raised error.
 * The major number XAR_NO_EXCEPTION identifies the state in which no error was raised.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @deprec 2006-01-12
 * @return integer the major value of raised error
 */
function xarCurrentErrorType()
{
    // return NO exception for code which tests for this, if there was an exception
    // it has already been raised before the code reaches this point.
    return XAR_NO_EXCEPTION;
}

/**
 * Gets the identifier of current error
 *
 * Returns the error identifier corresponding to the current error.
 * If invoked when no error was raised, a void value is returned.
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @deprec 2006-01-12
 * @return string the error identifier
 */
function xarCurrentErrorID()
{
    return;
}

/**
 * Gets the current error object
 *
 * Returns the value corresponding to the current error.
 * If invoked when no error or an error for which there is no associated information was raised, a void value is returned.
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @deprec 2006-01-12
 * @return mixed error value object
 */
function xarCurrentError()
{
    return;
}

/**
 * Resets current error status
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @deprec 2006-01-12
 * @return void
 */
function xarErrorFree()
{
    return;
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
    return;
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
 * @deprec 20060113
 * @return string the string representing the raised error
 */
function xarErrorRender($format,$stacktype = "ERROR", $data=array())
{
    return;
}

/**
 * Gets a formatted array of errors
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @deprec 2006-01-13
 * @return void
 */
function xarErrorGet($stacktype = "ERROR",$format='data')
{
    return;
}
?>
