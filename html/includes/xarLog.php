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
 * @subpackage Logging Facilities
 * @author Marco Canini <m.canini@libero.it>
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
// function xarLogMessage($message, $priority = 7) {
define('PEAR_LOG_EMERG',    0);
define('PEAR_LOG_ALERT',    1);
define('PEAR_LOG_CRIT',     2);
define('PEAR_LOG_ERR',      3);
define('PEAR_LOG_WARNING',  4);
define('PEAR_LOG_NOTICE',   5);
define('PEAR_LOG_INFO',     6);
define('PEAR_LOG_DEBUG',    7);

function xarLog_init($args, $whatElseIsGoingLoaded) {

    global $_xarLoggers;

    $_xarLoggers = array();

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

        if (!include_once ('modules/logger/drivers/'.xarVarPrepForOS($loggerName).'.php')) {
            xarCore_die('xarLog_init: Unable to load driver for logging: '.$loggerName);
        }

        $loggerName = 'xarLogger_'.$loggerName;

        if (!$observer = new $loggerName()) {
            xarCore_die('xarLog_init: Unable to load driver class for logging: '.$loggerName);
        }

        $observer->setConfig($loggerArgs);

        $_xarLoggers[] = &$observer;
    }
    
    return true;
}

function xarLogMessage($message, $priority = PEAR_LOG_DEBUG) {
    global $_xarLoggers;
    
    if (($priority == PEAR_LOG_DEBUG) && !xarCoreIsDebuggerActive()) return;
    foreach ($_xarLoggers as $logger) {
       $logger->notify($message, $priority);
    }
}

function xarLogException($priority = PEAR_LOG_DEBUG)
{
    //This wasnt implemented anywhere, supposedly it exists because of
    // a bug which cause a infinite loop (?)
    xarLogMessage("logException()", $priority);
}

function xarLogVariable($name, $var, $priority = PEAR_LOG_DEBUG)
{
    //This seems of dubial usefulness
    xarLogMessage("logVariable($name, ".print_r($var, TRUE).')', $priority);
}
?>
