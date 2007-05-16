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

   Q: the exception handler receives the instantiated Exception class.
      How do we know there what is available in the derived object so we can
      specialize handling? To only allow deriving from XARExceptions and
      standardize there is probably not enough, but lets do that for now.

*/

/**#@+
 * Error constants for exception throwing
 *
 * @todo probably move this to core loader or get rid of it completely, doesnt do something sane.
 */
define('E_XAR_ASSERT', 1);
define('E_XAR_PHPERR', 2);
/**#@-*/

// We need the new classes
sys::import('xaraya.exceptions.types');
// And the handlers to deal with them
sys::import('xaraya.exceptions.handlers');

/**
 * General exception to cater for situation where the called function should
 * really raise one and the callee should catch it, instead of the callee
 * raising the exception. To prevent hub-hopping* all over the code
 *
 * @todo we need a way to determine the usage of this, because each use
 *       signals a 'code out of place' error
**/
class GeneralException extends xarExceptions
{
    protected $message = "An unknown error occurred.";
    protected $hint    = "The code raised an exception, but the nature of the error could not be determind";
}

/**
 * Initializes the Error Handling System, basically all it does it register
 * the handler for exceptions and the handler for errors.
 *
 * @access protected
 * @return bool true
 */
function xarError_init(&$systemArgs)
{
    // Send all exceptions to the default exception handler, no excuses
    set_exception_handler(array('ExceptionHandlers','defaulthandler'));

    // Send all error the the default error handler (which basically just throws a specific exception)
    set_error_handler(array('ExceptionHandlers','phperrors'));

    return true;
}

/**
 * Debug function, artificially throws an exception
 *
 * @access public
 * @return void
 * @throws DebugException
**/
function debug($anything)
{
    throw new DebugException('DEBUGGING',var_export($anything,true));
}

?>
