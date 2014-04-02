<?php
/**
 * Logging Facilities
 *
 * @package core
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Flavio Botelho <nuncanada@ig.com.br>
 * @author Marcel van der Boom
 * @author Marc Lutolf
 * @todo  Document functions
 * @todo  Add options to simple & html logger
 * @todo  When calendar & xarLocaleFormatDate is done complete simple logger and html logger
 * @todo  When xarMail is done do email logger
**/

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
// This is a special define that includes all the levels defined above
define('XARLOG_LEVEL_ALL',       255);

/**
 * Exceptions raised within the loggers
 *
 */
class LoggerException extends Exception
{
    // Fill in later.
}

// Legacy calls

function xarLogConfigFile()
{   
    return xarLog::configFile(); 
}
function xarLogConfigReadable()
{   
    return xarLog::configReadable(); 
}
function xarLogFallbackFile()
{   
    return xarLog::fallbackFile(); 
}
function xarLogFallbackPossible()
{   
    return xarLog::fallbackPossible(); 
}
function xarLogMessage($message, $level = XARLOG_LEVEL_DEBUG)
{   
    return xarLog::message($message, $level); 
}
function xarLogVariable($name, $var, $level = XARLOG_LEVEL_DEBUG)
{   
    return xarLog::variable($name, $var, $level); 
}

class xarLog extends Object
{
    static private $configFile;
    static private $logFile;
    static public $loggers  = array();
    static public $config  = array();
    
    static public function init(&$args)
    {
        $GLOBALS['xarLog_loggers'] = array();
        
        // Only log if logging is enabled and if the config.system file is presents
        try {
            if (!xarSystemVars::get(sys::CONFIG, 'Log.Enabled')) return true;
        } catch (Exception $e) {
            return true;
        }
        
        $xarLogConfig = array();
    
        // Check if we have a log configuration file in the var directory
        if (self::configReadable())
        {
            // CHECKME: do we need to wrap this?
            if (!include (self::configFile())) {
                throw new LoggerException('xarLog_init: Log configuration file is invalid!');
            }
    
        // No file found. Try and fall back
        } elseif (self::fallbackPossible()) {
            //Fallback mechanism to allow some logging in important cases when
            //the user might not have logging yet installed, or for some reason we
            //should be able to have a way to get error messages back => installation?!
            $logFile = self::fallbackFile();
            if ($logFile) {
                $levels = @unserialize(xarSystemVars::get(sys::CONFIG, 'Log.Level'));
                if (!empty($levels)) {
                    $logLevel = 0;
                    $levels = explode(',', $levels);
                    foreach ($levels as $level) $logLevel |= (int)$level;
                } else {
                    $logLevel = XARLOG_LEVEL_ALL;
                }

                self::$config[] = array(
                    'type'      => 'simple',
                    'config'    => array(
                        'fileName' => $logFile,
                        'loglevel'  => $logLevel)
                        );
            }
        }
    
        // If none of these => do nothing.
         foreach (self::$config as $logger) {
            self::addLogger($logger['type'], $logger['config']);
         }
    
        // Subsystem initialized, register a shutdown function
        register_shutdown_function('xarLog__shutdown_handler');
    
        return true;
    }

    /**
     * Will return the log configuration file directory and name
     */
    static public function configFile()
    {
        if (isset(self::$configFile)) return self::$configFile;
    
        $logConfigFile = sys::varpath() . '/logs/config.log.php';
    
        if (file_exists($logConfigFile)) {
            self::$configFile = realpath($logConfigFile);
        }
        return self::$configFile;
    }

    /**
     * Will return true if the log config file exists and is readable, and false if not
     */
    static public function configReadable()
    {
        $logConfigFile = self::configFile();
    
        if (file_exists($logConfigFile) && is_readable($logConfigFile)) {
            return true;
        }
        return false;
    }

    /**
     * Will return the log file directory and name
     */
    static public function fallbackFile ()
    {
        if (isset(self::$logFile)) return self::$logFile;
    
        $logFile = sys::varpath() . '/logs/' . xarSystemVars::get(sys::CONFIG, 'Log.Filename');
        if (!file_exists($logFile)) touch($logFile);
        self::$logFile = realpath($logFile);
        return self::$logFile;
    }

    /**
     * Will check if the fallback mechanism can be used
     * @return boolean
     */
    static public function fallbackPossible ()
    {
        $logFile = self::fallbackFile();
        if (file_exists($logFile) && is_writeable($logFile)) {
            return true;
        }
        return false;
    }

    /**
     * Log a message
     * @param string message. The message to log
     * @param string level. The level for this message OPTIONAL Defaults to XARLOG_LEVEL_DEBUG
     *
     */
    static public function message($message, $level = XARLOG_LEVEL_DEBUG)
    {
        if (($level == XARLOG_LEVEL_DEBUG) && !xarCoreIsDebuggerActive()) return;
        // this makes a copy of the object, so the original $this->_buffer was never updated
        //foreach ($_xarLoggers as $logger) {
        foreach (array_keys(self::$loggers) as $id) {
           self::$loggers[$id]->notify($message, $level);
        }
    }
    
    static public function variable($name, $var, $level = XARLOG_LEVEL_DEBUG)
    {
        $args = array('name'=>$name, 'var'=>$var, 'format'=>'text');
    
        //Encapsulate core libraries in classes and let __call work lazy loading
        sys::import('xaraya.log.functions.dumpvariable');
        self::message(xarLog__dumpVariable($args),$level);
    }

    /**
     * Add a logger to active loggers
     *
     * @return void
     * @throws LoggerException
    **/
    static public function addLogger($type, $config_args)
    {
        sys::import('xaraya.log.loggers.'.$type);
        $type = 'xarLogger_'.$type;
    
        if (!$observer = new $type()) {
            throw new LoggerException('xarLog_init: Unable to instantiate class for logging: '.$type);
        }

        $observer->setConfig($config_args);
        self::$loggers[] = &$observer;
    }
}

/**
 * Shutdown handler for the logging system
 *
 */
function xarLog__shutdown_handler()
{
     xarLog::message("xarLog shutdown handler");
     xarLog::message("Leaving session: " . xarSession::getId() . " - User: " . xarUser::getVar('uname') . " ( ID: " . xarUser::getVar('id') . ")");

     // If the debugger was active, we can dispose it now.
     if(xarDebug::$flags & XARDBG_SQL) {
         xarLog::message("Total SQL queries: $GLOBALS[xarDebug_sqlCalls].");
     }

     if (xarDebug::$flags & XARDBG_ACTIVE) {
         $lmtime = explode(' ', microtime());
         $endTime = $lmtime[1] + $lmtime[0];
         $totalTime = ($endTime - xarDebug::$startTime);
         xarLog::message("Response was served in $totalTime seconds.");
     }

//During register_shutdown, it's already too late.
//fwrite presents problems during it.
//you can't use it with javascript/mozilla loggers...
//Maybe there should be a xaraya shutdown event?
/*
     xarLogMessage("xarLog shutdown handler: Ending all logging.");

    foreach (array_keys($GLOBALS['xarLog_loggers']) as $id) {
       $GLOBALS['xarLog_loggers'][$id]->;
    }
 */
}
?>