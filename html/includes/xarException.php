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
class Exception
{
    var $msg;
    var $id;
    var $defaults;
    var $title;
    var $short;
    var $long;
    var $hint;

    function Exception() {

    }

    function toString() { return $this->msg; }
    function setID($id) { $this->id = $id; }
    function load($id) {
        $this->title = $this->defaults[$id]['title'];
        $this->short = $this->defaults[$id]['short'];
        $this->long = $this->defaults[$id]['long'];
        $this->hint = $this->defaults[$id]['hint'];
    }
    function getTitle($id) { return $this->title; }
    function getShort($id) {
        if ($this->msg != '') return $this->msg;
        else return $this->short;
    }
    function getLong($id) { return $this->long; }
    function getHint($id) { return $this->hint; }
}

class SystemException extends Exception
{
    function SystemException($msg = '') {

        $this->msg = $msg;
        $this->defaults = array(
            'BAD_PARAM' => array(
                'title' => xarML('Bad Parameter'),
                'short' => xarML('A parameter encountered was bad.'),
                'long' => xarML('A parameter provided during this operation could not be validated, or was not accepted for other reasons.') ),
            'COMPILER ERROR' => array(
                'title' => xarML('Compliler error'),
                'short' => xarML('The Blocklayout compiler encountered an error.'),
                'long' => xarML('The Blocklayout compiler encountered an error it could not recover from. No specific information is available.') ),
            'CONTEXT_NOT_EXIST' => array(
                'title' => xarML('Context does not exist'),
                'short' => xarML('A context element was not found.'),
                'long' => xarML('This error is a catchall to describe situations where a context element (block, template, include) was expected but not found.') ),
            'DATABASE_ERROR' => array(
                'title' => xarML('Database Error'),
                'short' => xarML('An error was encountered while attempting a database operation.'),
                'long' => xarML('No further information is available.') ),
            'DATABASE_ERROR_QUERY' => array(
                'title' => xarML('Database Query Error'),
                'short' => xarML('An error was encountered while trying to execute a database query.'),
                'long' => xarML('A database query could not be executed, either because the query could not be understood or because it returned unexpected results.') ),
            'DEPRECATED_API' => array(
                'title' => xarML('Deprecated API'),
                'short' => xarML('A function call encountered belongs to a deprecated API.'),
                'long' => xarML('A function call encountered belongs to an old API and is therefore no longer supported.') ),
            'EMPTY_PARAM' => array(
                'title' => xarML('Empty Parameter'),
                'short' => xarML('A parameter value was not provided.'),
                'long' => xarML('A parameter was expected during this operation, but none was found.') ),
            'EVENT_NOT_REGISTERED' => array(
                'title' => xarML('Event is not registered'),
                'short' => xarML('An unknown event was encountered.'),
                'long' => xarML('A reference to an event was encountered that is unknown to the system.') ),
            'EXCEPTION_FAILURE' => array(
                'title' => xarML('Unknown system error'),
                'short' => xarML('An unknown system error was encountered.'),
                'long' => xarML('The error encountered is coming from the error system itself. Please help us correct this by filing a bug at <a href="http://bugs.xaraya.com/enter_bug.cgi?product=App%20-%20Core">bugs.xaraya.com</a>.') ),
            'FUNCTION_FAILED' => array(
                'title' => xarML('Function failed'),
                'short' => xarML('A call to a function returned a bad result.'),
                'long' => xarML('The function executed correctly, but the result was a failure.') ),
            'ID_NOT_EXIST' => array(
                'title' => xarML('Unknown ID'),
                'short' => xarML('An expected ID was not found.'),
                'long' => xarML('An item was requested by an ID that is not recognized. This could mean the item has been moved, changed, or does not exist.') ),
            'INVALID_ATTRIBUTE' => array(
                'title' => xarML('Invalid attribute'),
                'short' => xarML('The Blocklayout parser encountered an invalid attribute.'),
                'long' => xarML('No further information is available.') ),
            'INVALID_ENTITY' => array(
                'title' => xarML('Invalid entity'),
                'short' => xarML('The Blocklayout parser encountered an invalid entity.'),
                'long' => xarML('No further information is available.') ),
            'INVALID_FILE' => array(
                'title' => xarML('Invalid file'),
                'short' => xarML('The Blocklayout parser encountered an invalid file.'),
                'long' => xarML('The file was either not found, or could not be read properly.') ),
            'INVALID_INSTRUCTION' => array(
                'title' => xarML('Invalid instruction'),
                'short' => xarML('The Blocklayout parser encountered an invalid instruction.'),
                'long' => xarML('No further information is available.') ),
            'INVALID_SPECIALVARIABLE' => array(
                'title' => xarML('Invalid special variable'),
                'short' => xarML('The Blocklayout parser encountered a problem with a special variable.'),
                'long' => xarML('No further information is available.') ),
            'INVALID_SYNTAX' => array(
                'title' => xarML('Invalid syntax'),
                'short' => xarML('The Blocklayout parser encountered a syntax error.'),
                'long' => xarML('No further information is available.') ),
            'INVALID_TAG' => array(
                'title' => xarML('Invalid tag'),
                'short' => xarML('The Blocklayout parser encountered an invalid tag.'),
                'long' => xarML('The tag encountered does not conform to XML syntax.') ),
            'LOCALE_NOT_AVAILABLE' => array(
                'title' => xarML('Locale not available') ),
            'LOCALE_NOT_EXIST' => array(
                'title' => xarML('Locale does not exist'),
                'short' => xarML('An unknown locale was encountered.'),
                'long' => xarML('A reference to a locale was encountered that is unknown to the system.') ),
            'MISSING_ATTRIBUTE' => array(
                'title' => xarML('Missing attribute'),
                'short' => xarML('The Blocklayout parser could not find a tag attribute.'),
                'long' => xarML('A tag attribute required by syntax was not found.') ),
            'MISSING_PARAMETER' => array(
                'title' => xarML('Missing parameter'),
                'short' => xarML('The Blocklayout parser could not resolve a tag parameter.'),
                'long' => xarML('A tag parameter could not be resolved because of bad syntax.') ),
            'MODULE_DEPENDENCY' => array(
                'title' => xarML('Module Dependency'),
                'short' => xarML('A module call failed because of an unsatisifed dependency.'),
                'long' => xarML('The current module cannot execute because another module that must first be installed is not present.') ),
            'MODULE_FILE_NOT_EXIST' => array(
                'title' => xarML('Module file does not exist'),
                'short' => xarML('An operation requires a module file that cannot be found.'),
                'long' => xarML('The file may be missing, or its name may have changed.') ),
            'MODULE_FUNCTION_NOT_EXIST' => array(
                'title' => xarML('Module function does not exist'),
                'short' => xarML('A call has been made to a module function that cannot be found.'),
                'long' => xarML('The file in which the function was expected may be missing. If not, then the error may have occurred because the actual function has a different name, or does not exist.') ),
            'MODULE_NOT_ACTIVE' => array(
                'title' => xarML('Module is not active'),
                'short' => xarML('A call has been made to a module that is not active.'),
                'long' => xarML('A module was called that has not yet been activated/installed. Use the activate link in the modules module (Modules->ViewAll) to install modules.') ),
            'MODULE_NOT_EXIST' => array(
                'title' => xarML('Module does not exist'),
                'short' => xarML('A call has been made to a module that cannot be found'),
                'long' => xarML('A module was called that has not yet been installed or is not present. Use the activate link in the modules module (Modules->ViewAll) to install modules.') ),
            'NO_PERMISSION' => array(
                'title' => xarML('No Privilege'),
                'short' => xarML('You do not have the privileges for this operation.'),
                'long' => xarML('An operation was attempted for which your user has not been assigned privileges. Privileges must be assigned by the system administrator(s).') ),
            'NOT_LOGGED_IN' => array(
                'title' => xarML('Not logged in'),
                'short' => xarML('You are attempting an operation that is not allowed for the Anonymous user.'),
                'long' => xarML('An operation was encountered that requires the user to be logged in. If you are currently logged in please report this as a bug.') ),
            'NOT_IMPLEMENTED' => array(
                'title' => xarML('Not implemented') ),
            'SYSTEM_ERROR' => array(
                'title' => xarML('System error'),
                'short' => xarML('A system error was encountered.'),
                'long' => xarML('No further information is available.') ),
            'TEMPLATE_NOT_EXIST' => array(
                'title' => xarML('Template does not exist'),
                'short' => xarML('An unknown template name was encountered.'),
                'long' => xarML('An attempt was made to render a template that was not found. The template may have been removed or does not exist, or its name may have changed.') ),
            'THEME_NOT_EXIST' => array(
                'title' => xarML('Theme does not exist'),
                'short' => xarML('An unknown theme name was encountered.'),
                'long' => xarML('An attempt was made to display a theme that was not found. The theme may have been removed or does not exist, or its name may have changed.') ),
            'UNABLE_TO_LOAD' => array(
                'title' => xarML('Unable to load'),
                'short' => xarML('An error was encountered during a load operation.'),
                'long' => xarML('The system was unable to successfully load a component, such as a module or theme.') ),
            'UNKNOWN' => array(
                'title' => xarML('Unknown Error'),
                'short' => xarML('An unknown error was encountered.'),
                'long' => xarML('No further information is available.') ),
            'VARIABLE_NOT_REGISTERED' => array(
                'title' => xarML('Variable is not registered'),
                'short' => xarML('You are attempting to call a variable that the system does not recognize.'),
                'long' => xarML('An error was encountered during a call to a variable. The variable may have been removed, or its name changed.') ),
            'XML_PARSER_ERROR' => array(
                'title' => xarML('XML Parser Error'),
                'short' => xarML('The XML parser has encountered an error.'),
                'long' => xarML('The XML parser tried to execute a line that was not well formed.'))
        );
    }


    function load($id) {
        if (!array_key_exists($id, $this->defaults)) $id = "EXCEPTION_FAILURE";
        parent::load($id);
    }

    function toHTML() { return nl2br(xarVarPrepForDisplay($this->msg)) . '<br/>'; }
}

class DefaultUserException extends Exception
{
    var $link;

    function DefaultUserException($msg = '', $link = NULL)
    {
        $this->msg = $msg;
        $this->link = $link;
        $this->defaults = array(
            'ALREADY EXISTS' => array(
                'title' => xarML('Block type already exists'),
                'short' => xarML('An attempt was made to register a block type in a module that already exists.')),
            'BAD_DATA' => array(
                'title' => xarML('Bad Data'),
                'short' => xarML('The data provided was bad.'),
                'long' => xarML('The value provided during this operation could not be validated, or was not accepted for other reasons.')),
            'DUPLICATE_DATA' => array(
                'title' => xarML('Duplicate Data'),
                'short' => xarML('The data provided was a duplicate.'),
                'long' => xarML('A unique value was expected during this operation, but the value provided is a duplicate of an existing value.')),
            'FORBIDDEN_OPERATION' => array(
                'title' => xarML('Forbidden Operation'),
                'short' => xarML('The operation you are attempting is not allowed in the current circumstances.'),
                'long' => xarML("You may have clicked on the browser's back or refresh button and reattempted an operation that may not be repeated, or your browser may not have cookies enabled.")),
            'LOGIN_ERROR' => array(
                'title' => xarML('Login error'),
                'short' => xarML('A problem was encountered during the login process.'),
                'long' => xarML('No further information is available.')),
            'MISSING_DATA' => array(
                'title' => xarML('Missing Data'),
                'short' => xarML('The data is incomplete.'),
                'long' => xarML('A value was expected during this operation, but none was found.')),
            'MULTIPLE_INSTANCES' => array(
                'title' => xarML('Multiple instances'),
                'short' => xarML('A module contains more than once instance of the same block type.')),
            'WRONG_VERSION' => array(
                'title' => xarML('Wrong version'),
                'short' => xarML('The application version supplied is wrong.'))
        );
    }

    function load($id) {
        if (array_key_exists($id, $this->defaults)) parent::load($id);
        else {
            $this->title = $id;
            $this->short = "No further information available";
        }
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
class ErrorCollection extends Exception
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
            $value = $value; // possibly redundant
        } else {
            if ($major == XAR_SYSTEM_EXCEPTION) {
                $value = '';
            } else {
                $value = "No further information available.";
            }
        }

        if ($major == XAR_SYSTEM_EXCEPTION) {
            $value = new SystemException($value);
        } else {
            $value = new DefaultUserException($value);
        }
    }
    // value is now the appropriate exception object
    // add the exception ID
    $value->setID($exceptionId);

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
 * You must always call this function when you handle a caught exception or
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
 * You must always call this function when you handle a caught exception.
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

    foreach($GLOBALS['xarException_stack'] as $exception) {
        $data = array();

        if ($format == 'html') {
            if (method_exists($exception['value'], 'toHTML')) {
                $data['short'] = $exception['value']->toHTML();
            }
        } else {
            if (method_exists($exception['value'], 'toString')) {
                $data['short'] = $exception['value']->toString();
            }
        }

        switch ($exception['major']) {
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
        $data['type'] = $type;

        $showParams = xarCoreIsDebugFlagSet(XARDBG_SHOW_PARAMS_IN_BT);
        $text = '';

        if(!xarVarGetCached('installer','installing')) {
            $roles = new xarRoles();
            $admins = "Administrators";
            $admingroup = $roles->findRole("Administrators");
            $me = $roles->getRole(xarSessionGetVar('uid'));
            $imadmin = $me->isParent($admingroup);
        }
        else $imadmin = true;
        if ($format == 'html') {
          if ($exception['major'] != XAR_USER_EXCEPTION && $imadmin) {
                $stack = $exception['stack'];
                $text = "";
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
            if ($exception['major'] != XAR_USER_EXCEPTION && $imadmin) {
                $stack = $exception['stack'];
                $text = "";
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
       $thisexception = $exception['value'];
       $thisexception->load($exception['exceptionId']);
       $data['title'] = $thisexception->getTitle();
       $data['short'] = $thisexception->getShort();
       $data['long'] = $thisexception->getLong();
       $data['hint'] = $thisexception->getHint();
       $data['stack'] = $text;
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

    // Make cached files also display their source file if it's a template
    // This is just for convenience when giving support, as people will probably
    // not look in the CACHEKEYS file to mention the template.
    if($GLOBALS['xarTpl_cacheTemplates']) {
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

            if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                $id = xarExceptionId();
                $value = xarExceptionValue();
                if ($id == 'ERROR_COLLECTION') {
                    // add an exception to error collection
                    $value->exceptions[] = array('id' => 'PHP_ERROR',
                                                 'value' => new SystemException($msg));
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ErrorCollection', $value);
                    $value->setID('PHP_ERROR');
                } else {
                    // raise an error collection
                    $exc = new ErrorCollection();
                    $exc->exceptions[] = array('id' => $id,
                                               'value' => $value);
                    $exc->exceptions[] = array('id' => 'PHP_ERROR',
                                               'value' => new SystemException($msg));
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ErrorCollection', $exc);
                    $exc->setID('PHP_ERROR');
                }
            } else {
                // raise an error collection
                $exc = new ErrorCollection();
                $exc->exceptions[] = array('id' => 'PHP_ERROR',
                                           'value' => new SystemException($msg));
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ErrorCollection', $exc);
                $exc->setID('PHP_ERROR');
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