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
 * Interface for all Xaraya raised exceptions
 *
 */
interface IxarExceptions {
    /* Why can't i specify final here? */
    public function __construct($vars = null, $msg = null);
    public function getHint();
}

/**
 * Base class for all Xaraya exceptions
 *
 * Every part of Xaraya may derive their 
 * own Exception class if they see fit to do so
 * 
 */
abstract class xarExceptions extends Exception implements IxarExceptions
{
    // Variable parts in the message.
    protected $message   = "Missing Exception Info, please put the defaults for '\$message' and '\$variables' members in the derived exception class.";
    protected $variables = array();
    protected $hint      = "No hint available";

    /**
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
    final public function __construct($vars = null, $msg = null) 
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

    public function getHint()
    {
        // preserve protected status if peeps call it by reference (i'd say this is a php bug)
        $ret =$this->hint;
        return $ret;
    }
}

/**
 * System types
 */
// PHP errors (including assertions)
final class PHPException extends Exception 
{
}

// Debugging
class DebugException extends xarExceptions
{
    // Derived exception class should minimally proved the following 2
    protected $message ='Default "$message" for "DebugException" with "$variables" member with value: "#(1)"';
    protected $variables ='a variable value should normally be here';
}

/**
 * Xaraya exception types
 *
 * The ideal situation here is that we only have abstract classes
 * below to help the rest of the framework derive their exceptions
 * Since it is not ideal yet, some explicit exception types are 
 * also defined here now. Over time, the explicit ones should move
 * to their respective subsystems or modules.
 *
 */

// Let's start with the abstract classes we ar reasonably sure of
// Registration failures
abstract class RegistrationExceptions extends xarExceptions 
{}
// Validation failures
abstract class ValidationExceptions extends xarExceptions 
{}
// Not finding stuff
abstract class NotFoundExceptions extends xarExceptions 
{}
// Duplication failures
abstract class DuplicationExceptions extends xarExceptions
{}
// Configuration failures
abstract class ConfigurationExceptions extends xarExceptions
{}
// Deprecation exceptions
abstract class DeprecationExceptions extends xarExceptions 
{}


/* ANYTHING BELOW THIS LINE IS UP FOR REVIEW AND SHOULD PROBABLY BE MOVED OR REWRITTEN */

// Anything going wrong with parameters in functions and method derives from this
// FIXME: this is weak
// FIXME: it's probably better to bring this under validation? In some cases even assertions.
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

// Functions 
class FunctionNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The function "#(1)" could not be found or not be loaded.';
}
// ID's
class IDNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'An item was requested based on a unique identifier (ID), however, the ID: "#(1)" could not be found.';
}
// Files
class FileNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The file "#(1) could not be found.';
}
// Directories
class DirectoryNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The directory "#(1)" could not be found.';
}
// Generic data
// FIXME: this is too generic
class DataNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'The data requested could not be found';
}
// Variables
class VariableNotFoundException extends NotFoundExceptions
{
    protected $message = 'The variable "#(1)" could not be found';
}
// Classes
class ClassNotFoundException extends NotFoundExceptions
{
    protected $message = 'The class "#(1)" could not be found';
}

// Generic duplication exception
// TODO: go over the uses of this generic one and make them explicit for what was actually duplicated
class DuplicateException extends DuplicationExceptions
{ 
    protected $message = 'The #(1) "#(2)" already exists, no duplicates are allowed'; 
}

// Forbidden operation
// FIXME: What is this? validation?
class ForbiddenOperationException extends xarExceptions
{ 
    protected $message = 'The operation you are attempting is not allowed in the current circumstances.';
}

// Generic XML parse exception
// FIXME: this is isolated in MLS now, make those instance more specific and lose this one
class XMLParseException extends xarExceptions
{ 
    protected $message = 'The XML file "#(1)" could not be parsed. At line #(2): #(3)';
}

?>