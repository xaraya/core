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
    xarLogMessage(xarModAPIFunc('base','log','dumpvariable', $args), $level);
}
?>