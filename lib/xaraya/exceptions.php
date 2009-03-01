<?php
/**
 * Exception Handling System
 *
 * For all documentation about exceptions see RFC-0054
 *
 * @package exceptions
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo the exception handler receives the instantiated Exception class.
 *       How do we know there what is available in the derived object so we can
 *       specialize handling? To only allow deriving from XARExceptions and
 *       standardize there is probably not enough, but lets do that for now.
**/

// Import all our exception types and the core exception handlers
sys::import('xaraya.exceptions.types');
sys::import('xaraya.exceptions.handlers');

/**
 * Default settings for:
 * exceptions: send to 'default' handler
 * errors    : send to 'phperrors' handler
 *
 * Of course, any piece of code can set their own handler after this
 * is loaded, which is almost what we want.
 *
 * @todo do we want this abstracted?
**/
set_exception_handler(array('ExceptionHandlers','defaulthandler'));
set_error_handler(array('ExceptionHandlers','phperrors'));

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
