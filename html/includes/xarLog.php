<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Logging Facilities
// ----------------------------------------------------------------------

/* TODO:
 * Document functions
 * Add options to simple & html logger
 * When calendar & xarLocaleFormatDate is done complete simple logger
 * and html logger
 * When xarMail is done do email logger
 */

define('XARLOG_LEVEL_DEBUG', 1);
define('XARLOG_LEVEL_NOTICE', 2);
define('XARLOG_LEVEL_WARNING', 4);
define('XARLOG_LEVEL_ERROR', 8);

function xarLog_init($args, $whatElseIsGoingLoaded)
{
    global $xarLog_logger, $xarLog_level;

    $loggerName = $args['loggerName'];
    $loggerArgs = $args['loggerArgs'];
    switch ($args['level']) {
        case 'DEBUG':
            $xarLog_level = XARLOG_LEVEL_DEBUG;
            break;
        case 'NOTICE':
            $xarLog_level = XARLOG_LEVEL_NOTICE;
            break;
        case 'WARNING':
            $xarLog_level = XARLOG_LEVEL_WARNING;
            break;
        case 'ERROR':
            $xarLog_level = XARLOG_LEVEL_ERROR;
            break;
        default:
            xarCore_die('xarLog_init: Unknown logger level: '.$args['level']);
    }

    switch ($loggerName) {
        case 'dummy':
            $xarLog_logger = new xarLog__Logger($loggerArgs);
            break;
        case 'simple':
            $xarLog_logger = new xarLog__SimpleLogger($loggerArgs);
            break;
        case 'html':
            $xarLog_logger = new xarLog__HTMLLogger($loggerArgs);
            break;
        case 'javascript':
            $xarLog_logger = new xarLog__JavaScriptLogger($loggerArgs);
            break;
		    case 'mozjsconsole':
					$xarLog_logger = new xarLog__MozJSConsoleLogger($loggerArgs);
					break;
        case 'email':
            $xarLog_logger = new xarLog__EmailLogger($loggerArgs);
            break;
        default:
            xarCore_die('xarLog_init: Unknown logger name: '.$loggerName);
    }

    return true;
}

/**
 * gets current log level
 * @access public
 * @return log level
 */
function xarLogGetLevel()
{
    global $xarLog_level;
    return $xarLog_level;
}

// TODO: <marco> Move to logger module
/*
function logger_adminapi_getLogLevelInfo($level)
{
    switch ($level) {
        case XARLOG_LEVEL_DEBUG:
            $name = xarML('Debug level');
            $description = xarML('Logs everything.');
            break;
        case XARLOG_LEVEL_NOTICE:
            $name = xarML('Notice level');
            $description = xarML('Logs all except debugging messages.');
            break;
        case XARLOG_LEVEL_WARNING:
            $name = xarML('Warning level');
            $description = xarML('Logs only warning and errors.');
            break;
        case XARLOG_LEVEL_ERROR:
            $name = xarML('Error level');
            $description = xarML('Logs only errors.');
            break;
    }
    return array('name'=>$name, 'description'=>$description);
}
*/
// TODO: <marco> Move to logger module
/*
function logger_adminapi_listLoggers()
{
    $dummy = array('id' => 'dummy',
                   'name' => 'Dummy logger',
                   'description' => xarML('Doesn\'t log anything.'));
    $simple = array('id' => 'simple',
                    'name' => 'Simple logger',
                    'description' => xarML('Logs in a file in plain text.'));
    $html = array('id' => 'html',
                  'name' => 'Html logger',
                  'description' => xarML('Logs in a file in html.'));
    $javascript = array('id' => 'javascript',
                        'name' => 'JavaScript logger',
                        'description' => xarML('Logs into a browser window (useful for debug).'));
    $email = array('id' => 'email',
                   'name' => 'Email logger',
                   'description' => xarML('Logs as plain text and send it as an email message.'));
    return array($dummy, $simple, $html, $javascript, $email);
}
*/

/**
 * Converts a string in the form key1=value1;key2=value2 to an
 * array in the form ('key1'=>'value1', 'key2'=>'value2')
 */
// TODO: <marco> Move to logger module
/*
function logger_adminapi_parseArgsString($string)
{
    $args = array();
    $tmp = explode(';', $string);
    foreach($tmp as $param) {
        list($k, $v) = explode('=', $param);
        $k = trim($k);
        $v = trim($v);
        $args[$k] = $v;
    }
    return $args;
}
*/

function xarLogMessage($msg, $level = XARLOG_LEVEL_DEBUG)
{
    global $xarLog_logger, $xarLog_level;
    if ($level >= $xarLog_level) {
       if ($level == XARLOG_LEVEL_DEBUG && !xarCoreIsDebuggerActive()) return;
       $xarLog_logger->logMessage($msg);
    }
}

function xarLogException($level = XARLOG_LEVEL_DEBUG)
{
    global $xarLog_logger, $xarLog_level;
    if ($level >= $xarLog_level) {
       if ($level == XARLOG_LEVEL_DEBUG && !xarCoreIsDebuggerActive()) return;
       $xarLog_logger->logException();
    }
}

function xarLogVariable($name, $var, $level = XARLOG_LEVEL_DEBUG)
{
    global $xarLog_logger, $xarLog_level;
    if ($level >= $xarLog_level) {
       if ($level == XARLOG_LEVEL_DEBUG && !xarCoreIsDebuggerActive()) return;
       $xarLog_logger->logVariable($name, $var);
    }
}

class xarLog__Logger
{
    // private
    var $depth = 0;
    var $format = 'text';

    function xarLog__Logger($args)
    { /* nothing do do */ }

    function logMessage($msg, $callPrepForDisplay = true)
    { /* nothing do do */ }

    function logException()
    {
        $msg = xarExceptionRender($this->format);
        $this->logMessage($msg, false);
    }

    function logVariable($name, $var)
    {
        $msg = $this->dumpVariable($var, $name);
        $this->logMessage($msg, false);
    }

    /**
     * @access protected
     */
    function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @access protected
     */
    function formatLevel()
    {
        $level = xarLogGetLevel();
        switch ($level) {
            case XARLOG_LEVEL_DEBUG:
                return 'DEBUG';
            case XARLOG_LEVEL_NOTICE:
                return 'NOTICE';
            case XARLOG_LEVEL_WARNING:
                return 'WARNING';
            case XARLOG_LEVEL_ERROR:
                return 'ERROR';
        }
    }

    /**
     * @access protected
     */
    function getTimestamp()
    {
        // TODO: <marco> Use formatDate here
        return date('Y-m-d H:i:s');
    }

    /**
     * @access protected
     */
    function dumpVariable($var, $name = NULL, $classname = NULL)
    {
        if ($this->depth > 32) { 
            return 'Recursive Depth Exceeded';
        }

        if ($this->depth == 0) {
            $blank = '';
        } else {
            $blank = str_repeat(' ', $this->depth);
        }
        $this->depth += 1;

        $TYPE_COLOR = "red";
        $NAME_COLOR = "blue";
        $VALUE_COLOR = "purple";

        $str = '';

        if (isset($name)) {
            if ($this->format == 'html') {
                $str = "<font color=\"$NAME_COLOR\">".$blank.'Variable name: <b>'.
                       htmlspecialchars($name).'</b></font><br/>';
            } else {
                $str = $blank."Variable name: $name\n";
            }
        }

        $type = gettype($var);
        if (is_object($var)) {
            $str = $this->dumpVariable(get_object_vars($var), $name, get_class($var));
        } elseif (is_array($var)) {

            if (isset($classname)) {
                $type = 'class';
            } else {
                $type = 'array';
            }

            if ($this->format == 'html') {
                $str .= "<font color=\"$TYPE_COLOR\">".$blank."Variable type: $type</font><br/>";
            } else {
                $str .= $blank."Variable type: $type\n";
            }

            if ($this->format == 'html') {
                $str .= '{<br/><ul>';
            } else {
                $str .= $blank."{\n";
            }

            foreach($var as $key => $val) {
                $str .= $this->dumpVariable($val, $key);
            }

            if ($this->format == 'html') {
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
            if ($this->format == 'html') {
                $str .= "<font color=\"$TYPE_COLOR\">".$blank."Variable type: $type</font><br/>";
                $str .= "<font color=\"$VALUE_COLOR\">".$blank.'Variable value: "'.
                       htmlspecialchars($var).'"</font><br/><br/>';
            } else {
                $str .= $blank."Variable type: $type\n";
                $str .= $blank."Variable value: \"$var\"\n\n";
            }
        }

        $this->depth -= 1;
        return $str;
    }

}

// Implements a concrete logger, the most simple text based file logger.
class xarLog__SimpleLogger extends xarLog__Logger
{
    var $fileName;

    function xarLog__SimpleLogger($args)
    {
        // TODO: <marco> Base fileName & one log file per month or per week
        $this->fileName = $args['fileName'];
        /*$fd = @fopen($this->fileName, 'w');
        fclose($fd);*/
    }

    function logMessage($msg, $callPrepForDisplay = true)
    {
        if (!($fd = @fopen($this->fileName, 'a'))) return;
        $ts = $this->getTimestamp();
        $str = $ts . ' - ';
        $blanklen = strlen($str);
        $str .= '('.$this->formatLevel().') ';
        $str .= $this->formatString($msg, $blanklen);
        $str .= "\n";
        fwrite($fd, $str);
        fclose($fd);
    }

    /**
     * @access protected
     */
    function formatString($string, $blanklen)
    {
        $break = "\n".str_repeat(' ', $blanklen);
        $rows = explode("\n", $string);
        foreach($rows as $row) {
            $newrows[] = wordwrap($row, 79 - $blanklen, $break, 1);
        }
        return join($break, $newrows);
    }
}

class xarLog__HTMLLogger extends xarLog__Logger
{
    var $fileName;

    function xarLog__HTMLLogger($args)
    {
        // TODO: <marco> Base fileName & one log file per month or per week
        $this->fileName = $args['fileName'];

        if (file_exists($this->fileName) ||
            !($fd = @fopen($this->fileName, 'a'))) return;
        $str = "<html><head><title>Xaraya HTML Logger</title></head><body>";
        fwrite($fd, $str);
        fclose($fd);
    }

    function logMessage($msg, $callPrepForDisplay = true)
    {
        if (!($fd = @fopen($this->fileName, 'a'))) return;
        $str = $this->getTimestamp().' - ('.$this->formatLevel().')<br/>';
        if ($callPrepForDisplay) {
            $msg = xarVarPrepForDisplay($msg);
        }
        $str .= nl2br($msg).'<br/>';
        fwrite($fd, $str);
        fclose($fd);
    }

}

class xarLog__JavaScriptLogger extends xarLog__Logger
{
    var $buffer = '';

    function xarLog__JavaScriptLogger($args)
    {
        // Set the HTML format
        $this->setFormat('html');
        
        xarTplAddJavaScriptCode('body', 'JavaScriptLogger', $this->getWindowLoaderScript());
    }

    function getWindowLoaderScript()
    {
        $header = "<table size=\\\"100%\\\" cellspacing=\\\"0\\\" cellpadding=\\\"0\\\" border=\\\"0\\\"><tr><td>".
                  "<hr size=\\\"1\\\">Xaraya Javascript Logger</hr></td><td width=\\\"1%\\\"><font face=\\\"Verdana,arial\\\" size=\\\"1\\\">".
                  date("Y-m-d H:i:s").
                  "</font></td></tr></table>";

        $code = "debugWindow = window.open(\"Xaraya Javascript Logger\",\"Xaraya Javascript Logger\",\"width=450,height=500,scrollbars=yes,resizable=yes\");\n".
                "if (debugWindow) {\n".
                "    debugWindow.focus();\n".
                "    debugWindow.document.write(\"".$header."\"+'<p><b>'+window.location.href+'</b></p>');\n".
                "}\n";

        return $code;
    }

    function getBuffer()
    {
        $code = "<script language=\"javascript\">\n".
               "if (debugWindow) {\n".
               $this->buffer.
               "    debugWindow.scrollBy(0,100000);\n".
               "}\n".
               "</script>\n";
        return $code;
    }

    function logMessage($msg, $callPrepForDisplay = true)
    {
        $code = "if (debugWindow) {\n".
                "    debugWindow.document.write(\"".$this->getTimestamp().
               ' - ('.$this->formatLevel().')<br/>';
        if ($callPrepForDisplay) {
            $msg = xarVarPrepForDisplay($msg);
        }
        $msg = str_replace("\n", '', nl2br(addslashes($msg)));
        $code .= $msg . "<br/><br/>\");\n}\n";
        xarTplAddJavaScriptCode('body', 'JavaScriptLogger', $code);
    }

}

class xarLog__MozJSConsoleLogger extends xarLog__Logger
{
    var $buffer = '';
		var $loggerdesc="Mozilla Javascript Console Logger";

    function xarLog__MozJSConsoleLogger($args)
    {
        // Set the HTML format
        $this->setFormat('html');
		}

//     function getBuffer()
//     {
//         $code = "<script language=\"javascript\">\n".
// 					$this->buffer.
// 					"</script>\n";
//         return $str;
//     }

    function logMessage($msg, $callPrepForDisplay = true)
    {
			// FIXME: this code depends on a user setting to use principal codebase support (same origin policy)
			// it should be done with a signed script eventually, but this is rather complex 
			// TODO: check on windows and browsers other than mozilla, to fall back gracefully
			$code="netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');\n".
				"var con_service_class = Components.classes['@mozilla.org/consoleservice;1'];\n".
				"var iface = Components.interfaces.nsIConsoleService;\n".
				"var jsconsole = con_service_class.getService(iface);\n";
			$logentry=$this->getTimestamp(). " - (" .$this->formatLevel().")".$msg;
		  $code.= "jsconsole.logStringMessage('$logentry');\n";
			xarTplAddJavaScriptCode('body', 'Mozilla JS console logger', $code);
    }

}

class xarLog__EmailLogger extends xarLog__Logger
{
    function xarLog__EmailLogger($args)
    { die('TODO'); }

    function logMessage($msg, $callPrepForDisplay = true)
    { die('TODO'); }

    function logException()
    { die('TODO'); }

    function logVariable($name, $var)
    { die('TODO'); }

}

?>
