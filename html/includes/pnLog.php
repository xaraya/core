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
 * When calendar & pnLocaleFormatDate is done complete simple logger
 * and html logger
 * When pnMail is done do email logger
 */

define('PNLOG_LEVEL_DEBUG', 1);
define('PNLOG_LEVEL_NOTICE', 2);
define('PNLOG_LEVEL_WARNING', 4);
define('PNLOG_LEVEL_ERROR', 8);

function pnLog_init($args)
{
    global $pnLog_logger, $pnLog_level;

    $loggerName = $args['loggerName'];
    $loggerArgs = $args['loggerArgs'];
    switch ($args['level']) {
        case 'DEBUG':
            $pnLog_level = PNLOG_LEVEL_DEBUG;
            break;
        case 'NOTICE':
            $pnLog_level = PNLOG_LEVEL_NOTICE;
            break;
        case 'WARNING':
            $pnLog_level = PNLOG_LEVEL_WARNING;
            break;
        case 'ERROR':
            $pnLog_level = PNLOG_LEVEL_ERROR;
            break;
        default:
            die('pnLog_init: Unknown logger level: '.$args['level']);
    }

    switch ($loggerName) {
        case 'dummy':
            $pnLog_logger = new pnLog__Logger($loggerArgs);
            break;
        case 'simple':
            $pnLog_logger = new pnLog__SimpleLogger($loggerArgs);
            break;
        case 'html':
            $pnLog_logger = new pnLog__HTMLLogger($loggerArgs);
            break;
        case 'javascript':
            $pnLog_logger = new pnLog__JavaScriptLogger($loggerArgs);
            break;
        case 'email':
            $pnLog_logger = new pnLog__EmailLogger($loggerArgs);
            break;
        default:
            die('pnLog_init: Unknown logger name: '.$loggerName);
    }

    return true;
}

/**
 * gets current log level
 * @access public
 * @return log level
 */
function pnLogGetLevel()
{
    global $pnLog_level;
    return $pnLog_level;
}

// TODO: <marco> Move to logger module
/*
function logger_adminapi_getLogLevelInfo($level)
{
    switch ($level) {
        case PNLOG_LEVEL_DEBUG:
            $name = pnML('Debug level');
            $description = pnML('Logs everuthing.');
            break;
        case PNLOG_LEVEL_NOTICE:
            $name = pnML('Notice level');
            $description = pnML('Logs all except debugging messages.');
            break;
        case PNLOG_LEVEL_WARNING:
            $name = pnML('Warning level');
            $description = pnML('Logs only warning and errors.');
            break;
        case PNLOG_LEVEL_ERROR:
            $name = pnML('Error level');
            $description = pnML('Logs only errors.');
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
                   'description' => pnML('Doesn\'t log anything.'));
    $simple = array('id' => 'simple',
                    'name' => 'Simple logger',
                    'description' => pnML('Logs in a file in plain text.'));
    $html = array('id' => 'html',
                  'name' => 'Html logger',
                  'description' => pnML('Logs in a file in html.'));
    $javascript = array('id' => 'javascript',
                        'name' => 'JavaScript logger',
                        'description' => pnML('Logs into a browser window (useful for debug).'));
    $email = array('id' => 'email',
                   'name' => 'Email logger',
                   'description' => pnML('Logs as plain text and send it as an email message.'));
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

function pnLogMessage($msg, $level = PNLOG_LEVEL_DEBUG)
{
    global $pnLog_logger, $pnLog_level;
    if ($level >= $pnLog_level) {
       if ($level == PNLOG_LEVEL_DEBUG && !pnCoreIsDebuggerActive()) return;
       $pnLog_logger->logMessage($msg);
    }
}

function pnLogException($level = PNLOG_LEVEL_DEBUG)
{
    global $pnLog_logger, $pnLog_level;
    if ($level >= $pnLog_level) {
       if ($level == PNLOG_LEVEL_DEBUG && !pnCoreIsDebuggerActive()) return;
       $pnLog_logger->logException();
    }
}

function pnLogVariable($name, $var, $level = PNLOG_LEVEL_DEBUG)
{
    global $pnLog_logger, $pnLog_level;
    if ($level >= $pnLog_level) {
       if ($level == PNLOG_LEVEL_DEBUG && !pnCoreIsDebuggerActive()) return;
       $pnLog_logger->logVariable($name, $var);
    }
}

class pnLog__Logger
{
    // private
    var $depth = 0;
    var $format = 'text';

    function pnLog__Logger($args)
    { /* nothing do do */ }

    function logMessage($msg, $callPrepForDisplay = true)
    { /* nothing do do */ }

    function logException()
    {
        $msg = pnExceptionRender($this->format);
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
        $level = pnLogGetLevel();
        switch ($level) {
            case PNLOG_LEVEL_DEBUG:
                return 'DEBUG';
            case PNLOG_LEVEL_NOTICE:
                return 'NOTICE';
            case PNLOG_LEVEL_WARNING:
                return 'WARNING';
            case PNLOG_LEVEL_ERROR:
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
class pnLog__SimpleLogger extends pnLog__Logger
{
    var $fileName;

    function pnLog__SimpleLogger($args)
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

class pnLog__HTMLLogger extends pnLog__Logger
{
    var $fileName;

    function pnLog__HTMLLogger($args)
    {
        // TODO: <marco> Base fileName & one log file per month or per week
        $this->fileName = $args['fileName'];

        if (file_exists($this->fileName) ||
            !($fd = @fopen($this->fileName, 'a'))) return;
        $str = "<html><head><title>PostNuke HTML Logger</title></head><body>";
        fwrite($fd, $str);
        fclose($fd);
    }

    function logMessage($msg, $callPrepForDisplay = true)
    {
        if (!($fd = @fopen($this->fileName, 'a'))) return;
        $str = $this->getTimestamp().' - ('.$this->formatLevel().')<br/>';
        if ($callPrepForDisplay) {
            $msg = pnVarPrepForDisplay($msg);
        }
        $str .= nl2br($msg).'<br/>';
        fwrite($fd, $str);
        fclose($fd);
    }

}

function pnLog__JavaScriptLogger_OnPostBodyStart($value)
{
    // This function is called whenever the <body> tag has being sent to the browser
    global $pnLog_logger;
    echo $pnLog_logger->getWindowLoaderScript();
}

function pnLog__JavaScriptLogger_OnPreBodyEnd($value)
{
    // This function is called whenever the </body> tag is going to be sent to the browser
    global $pnLog_logger;
    echo $pnLog_logger->getBuffer();
}

class pnLog__JavaScriptLogger extends pnLog__Logger
{
    var $buffer = '';

    function pnLog__JavaScriptLogger($args)
    {
        // Register proper callback functions at EMS
        pnEvt_subscribeRawCallback('PostBodyStart', 'pnLog__JavaScriptLogger_OnPostBodyStart');
        pnEvt_subscribeRawCallback('PreBodyEnd', 'pnLog__JavaScriptLogger_OnPreBodyEnd');
        // Set the HTML format
        $this->setFormat('html');
    }

    function getWindowLoaderScript()
    {
        $header = "<table size=\\\"100%\\\" cellspacing=\\\"0\\\" cellpadding=\\\"0\\\" border=\\\"0\\\"><tr><td>".
                  "<hr size=\\\"1\\\">PostNuke Javascript Logger</hr></td><td width=\\\"1%\\\"><font face=\\\"Verdana,arial\\\" size=\\\"1\\\">".
                  date("Y-m-d H:i:s").
                  "</font></td></tr></table>";

        $str = "<script language=\"javascript\">\n".
               "debugWindow = window.open(\"PostNuke Javascript Logger\",\"PostNuke Javascript Logger\",\"width=450,height=500,scrollbars=yes,resizable=yes\");\n".
               "if (debugWindow) {\n".
               "    debugWindow.focus();\n".
               "    debugWindow.document.write(\"".$header."\"+'<p><b>'+window.location.href+'</b></p>');\n".
               "}\n".
               "</script>\n";
        return $str;
    }

    function getBuffer()
    {
        $str = "<script language=\"javascript\">\n".
               "if (debugWindow) {\n".
               $this->buffer.
               "    debugWindow.scrollBy(0,100000);\n".
               "}\n".
               "</script>\n";
        return $str;
    }

    function logMessage($msg, $callPrepForDisplay = true)
    {
        $str = "    debugWindow.document.write(\"".$this->getTimestamp().
               ' - ('.$this->formatLevel().')<br/>';
        if ($callPrepForDisplay) {
            $msg = pnVarPrepForDisplay($msg);
        }
        $msg = str_replace("\n", '', nl2br(addslashes($msg)));
        $str .= $msg . "<br/><br/>\");\n";
        $this->buffer .= $str;
    }

}

class pnLog__EmailLogger extends pnLog__Logger
{
    function pnLog__EmailLogger($args)
    { die('TODO'); }

    function logMessage($msg, $callPrepForDisplay = true)
    { die('TODO'); }

    function logException()
    { die('TODO'); }

    function logVariable($name, $var)
    { die('TODO'); }

}

?>
