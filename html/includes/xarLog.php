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

define('XARLOG_LEVEL_EMERGENCY', 1);
define('XARLOG_LEVEL_ALERT',     2);
define('XARLOG_LEVEL_CRITICAL',  4);
define('XARLOG_LEVEL_ERROR',     8);
define('XARLOG_LEVEL_WARNING',   16);
define('XARLOG_LEVEL_NOTICE',    32);
define('XARLOG_LEVEL_INFO',      64);
define('XARLOG_LEVEL_DEBUG',     128);

function xarLog_init($args, $whatElseIsGoingLoaded) 
{

    $GLOBALS['xarLog_loggers'] = array();

    $logConfigFile = xarCoreGetVarDirPath() . '/cache/config.log.php';

    $xarLogConfig = array();

    if (file_exists($logConfigFile)) {
        if (!include_once ($logConfigFile)) {
            xarCore_die('xarLog_init: Log configuration file is invalid!');
        }

    //FIXME: This fallback should disappear some time after the logconfig module is consolidated 
    } elseif (isset($args['loggerName']) && ($args['loggerName'] != NULL)) {
        //Fallback for the older configuration within the config.php
        
        // If someone doesnt want logging, then dont event load any code.
        if ($args['loggerName'] == 'dummy') {
            //Do nothing
        } else {
            if (!isset($args['loggerArgs']['maxLevel'])) {
                $args['loggerArgs']['maxLevel'] = $args['level'];
            }
            
            //Lazy load these functions... With php5 this will be easier.
            //Encapsulate core libraries in classes and let __call work lazy loading
            include_once('includes/log/functions/stringtolevel.php');
            $args['loggerArgs']['logLevel'] = 2 * (xarLog__stringToLevel($args['loggerArgs']['maxLevel'])) - 1;

            $xarLogConfig[] = array('type'    => $args['loggerName'],
                                                      'config' => $args['loggerArgs']);
        }
    } else {
        //Fallback mechanism to allow some logging in important cases when
        //the user might now have logging yet installed, or for some reason we
        //should be able to have a way to get error messages back => installation?!
        $logFile = xarCoreGetVarDirPath() . '/logs/log.html';
        if (file_exists($logFile) && is_writeable($logFile)) {
            $xarLogConfig[] = array('type' => 'html',
                                                     'config' =>array('fileName' => $logFile,
                                                                                  'logLevel'  => (2 * XARLOG_LEVEL_DEBUG -1)));
        }
    }

    // If none of these => do nothing.
     foreach ($xarLogConfig as $logger) {
         xarLog__add_logger($logger['type'], $logger['config']);
     }

    // Subsystem initialized, register a shutdown function
    register_shutdown_function('xarLog__shutdown_handler');

    return true;
}

/**
 * Shutdown handler for the logging system
 *
 *
 */
function xarLog__shutdown_handler()
{
     xarLogMessage("xarLog shutdown handler.");
    
    // If the debugger was active, we can dispose it now.
    if($GLOBALS['xarDebug'] & XARDBG_SQL) {
        xarLogMessage("Total SQL queries: $GLOBALS[xarDebug_sqlCalls].");
    }

    if ($GLOBALS['xarDebug'] & XARDBG_ACTIVE) {
        $lmtime = explode(' ', microtime());
        $endTime = $lmtime[1] + $lmtime[0];
        $totalTime = ($endTime - $GLOBALS['xarDebug_startTime']);
        xarLogMessage("Response was served in $totalTime seconds.");
    }

/*
     xarLogMessage("xarLog shutdown handler: Ending all logging.");

    foreach (array_keys($GLOBALS['xarLog_loggers']) as $id) {
       $GLOBALS['xarLog_loggers'][$id]->;
    }
 */
}

function xarLog__add_logger($type, $config_args)
{
    if (!include_once ('./includes/log/loggers/'.xarVarPrepForOS($type).'.php')) {
        xarCore_die('xarLog_init: Unable to load driver for logging: '.$type);
    }

    $type = 'xarLogger_'.$type;

     if (!$observer = new $type()) {
        xarCore_die('xarLog_init: Unable to instanciate class for logging: '.$type);
     }

      $observer->setConfig($config_args);

      $GLOBALS['xarLog_loggers'][] = &$observer;
}

function xarLogMessage($message, $level = XARLOG_LEVEL_DEBUG) 
{

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

    //Lazy load these functions... With php5 this will be easier.
    //Encapsulate core libraries in classes and let __call work lazy loading
    include_once('var/log/functions/dumpvariable.php');
    xarLogMessage(xarLog__dumpVariable($args),$level);
}

?>