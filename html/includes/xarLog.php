<?php
/**
 * File: $Id$
 *
 * Logging Facilities
 *
 * @package logging
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Flavio Botelho <nuncanada@ig.com.br>
 * @todo  Document functions
 *        Add options to simple & html logger
 *        When calendar & xarLocaleFormatDate is done complete simple logger
 *        and html logger
 *        When xarMail is done do email logger
 */

/**
 * Logging package defines
 */
//<nuncanada>These defines are useless if logging is not set up
// Didnt take them out, because it would be 'ugly' to make
// function xarLogMessage($message, $level = 255) {
define('XARLOG_LEVEL_EMERGENCY', 1);
define('XARLOG_LEVEL_ALERT',     2);
define('XARLOG_LEVEL_CRITICAL',  4);
define('XARLOG_LEVEL_ERROR',     8);
define('XARLOG_LEVEL_WARNING',   16);
define('XARLOG_LEVEL_NOTICE',    32);
define('XARLOG_LEVEL_INFO',      64);
define('XARLOG_LEVEL_DEBUG',     128);

function xarLog_init($args, $whatElseIsGoingLoaded) {

    $GLOBALS['xarLog_loggers'] = array();

    //<nuncanada>Can we change the config file to accomodate multiple loggers?

    $loggerName = $args['loggerName'];
    $loggerArgs = $args['loggerArgs'];

    //This makes the level sensitivity an option of the logger
    //So later we will be able to add a logger for everything in a place
    //And set up a mailer logger for some kind of bigger problems, alerting devs...
    //That would could be even default, giving us back info for fixing bugs on time :)
    // We could have our own Talkback feature :)
    if (!isset($loggerArgs['maxLevel'])) {
        $loggerArgs['maxLevel'] = $args['level'];
    }

    // If someone doesnt want logging, then dont event load any code.
    if ($loggerName != 'dummy') {

        if (!include_once ('./includes/loggers/'.xarVarPrepForOS($loggerName).'.php')) {
            xarCore_die('xarLog_init: Unable to load driver for logging: '.$loggerName);
        }

        $loggerName = 'xarLogger_'.$loggerName;

        if (!$observer = new $loggerName()) {
            xarCore_die('xarLog_init: Unable to load driver class for logging: '.$loggerName);
        }

        $observer->setConfig($loggerArgs);

        $GLOBALS['xarLog_loggers'][] = &$observer;
    }

    return true;
}

function xarLogMessage($message, $level = XARLOG_LEVEL_DEBUG) {

    if (($level == XARLOG_LEVEL_DEBUG) && !xarCoreIsDebuggerActive()) return;
    // this makes a copy of the object, so the original $this->_buffer was never updated
    //foreach ($_xarLoggers as $logger) {
    foreach (array_keys($GLOBALS['xarLog_loggers']) as $id) {
       $GLOBALS['xarLog_loggers'][$id]->notify($message, $level);
    }
}

function xarLogVariable($name, $var, $level = XARLOG_LEVEL_DEBUG)
{
    $args = array('name'=>$name, 'var'=>$var, 'format'=>'text');
    // no go for the xarModAPIFunc, that subsystem is not there yet
    
    xarLogMessage(xarLog__dumpVariable($args,$level));
}


/**
 *  Helper function for variable logging
 *
 * @todo This function came from logger api in base module, which is
 *       a no go in this subsystem, as the module subsystem may not exist
 *       I copied the literal function in here.
 * FIXME: this needs to be done by the logger classes in the loggers subdirectory
 *
 */
function xarLog__dumpVariable ($array)
{

    static $depth = 0;

    // $var, $name, $classname and $format
    extract($array);

    if ($depth > 32) {
        return 'Recursive Depth Exceeded';
    }
    
    if ($depth == 0) {
        $blank = '';
    } else {
        $blank = str_repeat(' ', $depth);
    }
    $depth += 1;
    
    $TYPE_COLOR = "#FF0000";
    $NAME_COLOR = "#0000FF";
    $VALUE_COLOR = "#999900";
    
    $str = '';
    
    if (isset($name)) {
        if ($format == 'html') {
            $str = "<span style=\"color: $NAME_COLOR;\">".$blank.'Variable name: <b>'.
                htmlspecialchars($name).'</b></span><br/>';
        } else {
            $str = $blank."Variable name: $name\n";
        }
    }
    
    $type = gettype($var);
    if (is_object($var)) {
        $args = array('name'=>$name, 'var'=>get_object_vars($var), 'classname'=>get_class($var), 'format'=>$format);
        // RECURSIVE CALL
        $str = xarLog__dumpVariable($args);
    } elseif (is_array($var)) {
        
        if (isset($classname)) {
            $type = 'class';
        } else {
            $type = 'array';
        }
        
        if ($format == 'html') {
            $str .= "<span style=\"color: $TYPE_COLOR;\">".$blank."Variable type: $type</span><br/>";
        } else {
            $str .= $blank."Variable type: $type\n";
        }
        
        if ($format == 'html') {
            $str .= '{<br/><ul>';
        } else {
            $str .= $blank."{\n";
        }
        
        foreach($var as $key => $val) {
            $args = array('name'=>$key, 'var'=>$val, 'format'=>$format);
            // RECURSIVE CALL
            $str .= xarLog__dumpVariable($args);
        }
        
        if ($format == 'html') {
            $str .= '</ul>}<br/><br/>';
        } else {
            $str .= $blank."}\n\n";
        }
    } else {
        if ($var === NULL) {
            $var = 'NULL';
        } else if ($var === false) {
            $var = 'false';
        } else if ($var === true) {
            $var = 'true';
        }
        if ($format == 'html') {
            $str .= "<span style=\"color: $TYPE_COLOR;\">".$blank."Variable type: $type</span><br/>";
            $str .= "<span style=\"color: $VALUE_COLOR;\">".$blank.'Variable value: "'.
                htmlspecialchars($var).'"</span><br/><br/>';
        } else {
            $str .= $blank."Variable type: $type\n";
            $str .= $blank."Variable value: \"$var\"\n\n";
        }
    }
    
    $depth -= 1;
    return $str;
}

?>