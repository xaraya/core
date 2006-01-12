<?php
/**
 * Exception types
 *
 * @package exceptions
 * @copyright (C) 2006 by The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/**
 * Class to deal with our exceptions in a uniform way.
 *
 */
interface IxarExceptions {
    public function __construct($vars = NULL, $msg = NULL);
}

/* Our own exceptions, the base container class, cannot be instantiated */
abstract class xarExceptions extends Exception implements IxarExceptions
{
    // Variable parts in the message.
    protected $message ="Missing Exception Info, please put the defaults for '\$message' and '\$variables' members in the derived exception class.";
    protected $variables = array();
    /*
     All exceptions have the same interface from XAR point of view
     so we dont allow this to be overridden just now. The message parameter
     may be overridden though. If not supplied the default message
     for the class gets used.
     Throwing an exeception is done by: 
         throw new WhateverException($vars);
     $vars is an array of values which are variable in the message. 
     The message is normally not overridden but possible., example:
         throw new FileNotFoundException(array($file,$dir),'Go place the file #(1) in the #(2) location, i can not find it');
    */
    final public function __construct($vars = NULL, $msg = NULL) 
    {
        // Make sure the construction creates the right values first
        if(!is_null($msg)) $this->message = $msg;
        parent::__construct($this->message,$this->code);

        if(!is_null($vars)) $this->variables = $vars;
        if(!is_array($this->variables)) $this->variables = array($this->variables);
        $rep=1;
        foreach($this->variables as $var) 
            $this->message = str_replace("#(".$rep++.")",(string)$var,$this->message);
    }
}

/**
 * System types
 */
// PHP errors
final class PHPException extends Exception 
{}

// Assertion failure
final class SRCException extends Exception 
{}

// Debugging
class DebugException extends xarExceptions
{
    // Derived exception class should minimally proved the following 2
    protected $message ='Default "$message" for "DebugException" with "$variables" member with value: "#(1)"';
    protected $variables ='a variable value should normally be here';
}


/**
 * Parameter exceptions
 */
// The base class
abstract class ParameterExceptions extends xarExceptions 
{}
// Empty required parameters
class EmptyParameterException extends ParameterExceptions
{ 
    protected $message = "The parameter '#(1)' was expected in a call to a function, but was not provided.";
}
// Bad values in parameters
class BadParameterException extends ParameterExceptions
{
    protected $message = "The parameter '#(1)' provided during this operation could not be validated, or was not accepted for other reasons.";
}

/**
 * Things which could not be found
 */

// The base class
abstract class NotFoundExceptions extends xarExceptions 
{}
// Function 
class FunctionNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The function "#(1)" could not be found or not be loaded.';
}
// ID 
class IDNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'An item was requested based on a unique identifier (ID), however, the ID: "#(1)" could not be found.';
}
// File
class FileNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The file "#(1) could not be found.';
}
// Directory
class DirectoryNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The directory "#(1) could not be found.';
}
// Base info for module
class ModuleBaseInfoNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The base info for module "#(1)" could not be found';
}
// Module
class ModuleNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'A module is missing, the module name could not be determined in the current context';
}
// Theme
class ThemeNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'A theme is missing, the theme name could not be determined in the current context';
}
// Locale
class LocaleNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The locale "#(1)" could not be found or is currently unavailable';
}
// Generic data
class DataNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The data requested could not be found';
}

/** 
 * Some things which are there, but not active
 */
// Module
class ModuleNotActiveException extends xarExceptions
{ 
    protected $message = 'The module "#(1)" was called, but it is not active.';
}
// User (kinda)
class NotLoggedInException extends xarExceptions
{ 
    protected $message = 'An operation was encountered that requires the user to be logged in. If you are currently logged in please report this as a bug.';
}

/**
 * Registration failures
 */
// The base class
abstract class RegistrationExceptions extends xarExceptions 
{}
// Variables
class VariableRegistrationException extends RegistrationExceptions
{ 
    protected $message = 'Variable "#(1)" is not properly registered';
}
// Events
class EventRegistrationException extends RegistrationExceptions
{ 
    protected $message = 'The event "#(1)" is not properly registered';
}
// Tags
class TagRegistrationException extends RegistrationExceptions
{ 
    protected $message = 'The tag "#(1)" is not properly registered';
}


// Forbidden operation
class ForbiddenOperationException extends xarExceptions
{ 
    protected $message = 'The operation you are attempting is not allowed in the current circumstances.';
}

// Duplication
class DuplicateException extends xarExceptions
{ 
    protected $message = 'The #(1) "#(2)" already exists, no duplicates are allowed'; 
}
class DuplicateTagException extends xarExceptions
{ 
    protected $message = 'The tag definition for the tag: "#(1)" already exists.';
}

// Validation
abstract class ValidationExceptions extends xarExceptions {}
// BL
class BLValidationException extends xarExceptions
{ 
    protected $message = 'A blocklayout tag or attribute construct was invalid, see the tag documentation for the correct syntax';
}
// Variables
class VariableValidationException extends ValidationExceptions
{ 
    protected $message = 'The variable "#(1)" [Value: "#(2)"] did not comply with the required validation: "#(3)"';
}

// Configuration
class ConfigurationException extends xarExceptions
{ 
    protected $message = 'There is an unknown configuration error detected.';
}

class XMLParseException extends xarExceptions
{ 
    protected $message = 'The XML file "#(1)" could not be parsed. At line #(2): #(3)';
}

// Deprecation
abstract class DeprecationExceptions extends xarExceptions 
{}
// API
class ApiDeprecationException extends DeprecationExceptions
{ 
    protected $message = "You are trying to use a deprecated API function [#(1)], Replace this call with #(2)";
}
// Errors
class ErrorDeprecationException extends DeprecationExceptions
{
    protected $message ="This exception was called through a deprecated API (usually xarErrorSet).\n You should not use xarErrorSet anymore, but raise/catch real exceptions.\nThis was the original error: #(1)";
}

class BLException extends xarExceptions 
{ 
    protected $message = 'Unknown blocklayout exception (TODO)';
}

?>