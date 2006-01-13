<?php
/**
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/* RFC MATERIAL follows:

   Exception classes we will probably need:
   I'd say that each subsystem or component can derive an exception class from (XAR)Exceptions
   class and provide there whatever it needs.

   The exception classes should be defined with reasonably meaningful names, so the catch clause(s)
   remain readable by a human too. While this is a bit longer to type, the added value when debugging
   someone elses code is invaluable.

   A derivation tree COULD be: ( items marked with * are provided here, others can come from somewhere else)
   This is just to give an idea what the tree could look like, we just provide the * marked items here and
   the required interface for derived classes.

   Exception [PHP]
   |-->[3rdPartyExceptions go here]
   |-->SQLException [Creole] - signals exception in SQL backend.
   |-->PHPException* - the php error handler raised the exception, means that a PHP error occurred.
   |-->SRCException* - the assertion handler raised the exception, means that an assertion in the code has failed.
   |-->xarExceptions*
       |-->DebugException* - debug Exception, i imagine we should be able to enable/disable this at will very easy so we can quickly test things.
       |-->NotFoundExceptions
       |   |-->FileNotFoundException
       |   |-->IDNotFoundException
       |   |-->LocaleNotFoundException
       |-->DuplicateExceptions
       |   |-->FileDuplicateException
       |   |-->BlockDuplicateException
       |-->ValidationExceptions
       |   |-->XMLValidationException
       |   |-->InputValidationException
       |-->ConfigurationExceptions
       |-->DeprecationExceptions
       |   |-->APIDeprecationException
       |   |-->SyntaxDeprecationException
       |-->SecurityExceptions
       |   |-->AuthenticationSecurityException
       |   |-->AuthorisationSecurityException
       |-->TranslationException
       |-->RegistrationExceptions
       |   |-->TagRegistrationException
       |   |-->EventRegistrationException
       |-->DependencyExceptions
       |   |-->VersionDependencyException

   The default interface of the php internal base Exception class is:
   new Exception(String $message, Int $code);
   (see also: http://www.php.net/manual/en/language.exceptions.php)
   Overridden exception classes however must implement the interface of the xarExceptions class however

   NOTE: Pay special attention in the above to the use of plural forms for container classes, so they can
         be caught all at once like:
         try {
            ..something risky..
         } catch(xarExceptions $e) {
           .. any Xar exception will be caught here, but no others
         }

   NOTE: I'm putting stuff on this all in this file now, we can split things up later on

   Q: do we need compatability classes for the legacy classes?
   Q: the exception handler receives the instantiated Exception class. 
      How do we know there what is available in the derived object so we can specialize handling?
      To only allow deriving from XARExceptions and standardize there is probably not enough, but lets do that for now.

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

// Include the set of exception types
include "includes/exceptions/types.php";
// And the handlers to deal with them
include "includes/exceptions/handlers.php";

/**
 * Initializes the Error Handling System
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @return bool true
 * @todo   can we move the stacks above into the init?
 */
function xarError_init($systemArgs, $whatToLoad)
{
    // Send all exceptions to the default exception handler, no excuses
    set_exception_handler(array('ExceptionHandlers','defaulthandler'));

    // Do we want our error handler or the native one?
    // FIXME: do we still want this variable, seems odd
    if ($systemArgs['enablePHPErrorHandler'] == true ) { 
        set_error_handler(array('ExceptionHandlers','phperrors'));
    }

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarError__shutdown_handler');
    return true;
}

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
 * @return void
 */
function xarErrorSet($major, $errorID, $value = NULL)
{
    // MINIMAL backward compatability 

    // If $value is a descendant from the old xarException class, get the message from it
    if(is_a($value,'xarException')) {
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
    return;
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

// PRIVATE FUNCTIONS

/**
 * Error handlers section
 *
 * For several areas there are specific bridges to route errors into
 * the exception subsystem:
 *
 * Handlers: (most of them have moved to exceptions/handlers.php
 * 1. assert failures -> xarException__assertErrorHandler($script,$line,$code)
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
    // TODO: classify the exception, we never want to use the base object directly.
    throw new SRCException($msg, E_XAR_ASSERT);
}

?>
