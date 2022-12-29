<?php
/**
 * Exceptions raised within the loggers
 *
 * @package core\exceptions
 * @subpackage exceptions
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
class LoggerException extends Exception
{
    // Fill in later.
}

/**
 * Logging Facilities
 *
 * @package core\logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Flavio Botelho <nuncanada@ig.com.br>
 * @author Marcel van der Boom
 * @author Marc Lutolf
 * @todo  Document functions
 * @todo  Add options to simple & html logger
 * @todo  When calendar & xarLocale::formatDate is done complete simple logger and html logger
 * @todo  When xarMail is done do email logger
**/

class xarLog extends xarObject
{
    const LEVEL_EMERGENCY  = 1;
    const LEVEL_ALERT      = 2;
    const LEVEL_CRITICAL   = 4;
    const LEVEL_ERROR      = 8;
    const LEVEL_WARNING    = 16;
    const LEVEL_NOTICE     = 32;
    const LEVEL_INFO       = 64;
    const LEVEL_DEBUG      = 128;
// This is a special define that includes all the levels defined above
    const LEVEL_ALL        = 255;

    static private $configFile;
    static private $logFile;
    static public $loggers  = array();
    static public $config  = array();
    
    static public function init(array $args = array())
    {
        // Only log if logging is enabled and if the config.system file is present
        // Of course, if this file doesn't exist then Xaraya is already kaputt :)
        try {
            if (!xarSystemVars::get(sys::CONFIG, 'Log.Enabled')) return true;
        } catch (Exception $e) {
            return true;
        }

		// Get the available loggers as an array
		$availables = self::availables();

        // Check if we have a log configuration file in the var directory
        if (self::configReadable()) {
            // CHECKME: do we need to wrap this?
            if (!include (self::configFile())) {
                throw new LoggerException('xarLog_init: Log configuration file is invalid!');
            }
            
            $vararray = ['Filename', 'MaxFileSize', 'Level', 'Mode', 'Recipient', 'Sender', 'Subject', 'Timeformat','SQLTable', 'Facility', 'Options', 'SQLTable'];

            // Get the  each of the available loggers
            foreach ($availables as $available) {
            	
            	$config = array('type' => $available, 'fallback' => false);
				foreach ($vararray as $thisvar) {
					$varname = 'Log.' . ucwords($available) . '.' . $thisvar;
					if (isset($systemConfiguration[$varname])) {
						if ($thisvar == 'Filename') {
							$config[strtolower($thisvar)] = sys::varpath() . '/logs/' . $systemConfiguration[$varname];
						} else {
							$config[strtolower($thisvar)] = $systemConfiguration[$varname];
						}
					}
				}
                self::$config[] = array(
                    'type'      => $available,
                    'config'    => $config,
                        );
            }
        } else {
			throw new LoggerException('xarLog_init: Did not find the log configuration file at var/logs/config.log.php!');
        }

        // If logging is enabled but no loggers are active, try to fall back
        if ((int)xarSystemVars::get(sys::CONFIG, 'Log.Enabled') && empty($availables) && self::fallbackPossible()) {
            //Fallback mechanism to allow some logging in important cases when
            //the user might not have logging yet installed, or for some reason we
            //should be able to have a way to get error messages back => installation?!
            $logFile = self::fallbackFile();
            if ($logFile) {
                $levels = @unserialize(xarSystemVars::get(sys::CONFIG, 'Log.Level'));

                self::$config[] = array(
                    'type'          => 'simple',
                    'config'        => array(
                        'filename'  => $logFile,
                        'level'     => $levels,
                        'type'      => 'simple',
                        'fallback'  => true)
                        );
            }
        }

        // Activate each of the available loggers
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
    static public function fallbackFile()
    {
        if (isset(self::$logFile)) return self::$logFile;
    
        $logFile = sys::varpath() . '/logs/' . xarSystemVars::get(sys::CONFIG, 'Log.Filename');
        if (!file_exists($logFile)) touch($logFile);
        self::$logFile = $logFile;
        return self::$logFile;
    }

    /**
     * Will check if the fallback mechanism can be used
     * @return boolean
     */
    static public function fallbackPossible()
    {
        $logFile = self::fallbackFile();
        if (file_exists($logFile) && is_writeable($logFile)) {
            return true;
        }
        return false;
    }

    /**
     * Will return the loggers that are set as active
     * @return array
     */
    static public function availables()
    {
		// Get the available loggers as an array
		$availables = xarSystemVars::get(sys::CONFIG, 'Log.Available');
		if (!empty($availables)) $availables = explode(',', $availables);
		else $availables = array();
        return $availables;
    }
    
    /**
     * Log a message
     * @param string message. The message to log
     * @param string level. The level for this message OPTIONAL Defaults to XARLOG_LEVEL_DEBUG
     *
     */
    static public function message($message, $level = self::LEVEL_DEBUG)
    {
        if (empty($level)) $level = xarLog::LEVEL_DEBUG;
        if (($level == self::LEVEL_DEBUG) && !xarCore::isDebuggerActive()) return;

        // this makes a copy of the object, so the original $this->_buffer was never updated
        //foreach ($_xarLoggers as $logger) {
        foreach (array_keys(self::$loggers) as $id) {
           self::$loggers[$id]->notify($message, $level);
        }
    }
    
    static public function variable($name, $var, $level = '')
    {
        if (empty($level)) $level = self::LEVEL_DEBUG;
        $args = array('name'=>$name, 'var'=>$var, 'format'=>'text');
    
        //Encapsulate core libraries in classes and let __call work lazy loading
        sys::import('xaraya.log.functions.dumpvariable');
        self::message(xarLog__dumpVariable($args),$level);
    }

    /**
     * Add a logger to the active loggers
     *
     * @return void
     * @throws LoggerException
    **/
    static public function addLogger($type, $config_args)
    {
        sys::import('xaraya.log.loggers.' . $type);
        $logger = 'xarLogger_' . $type;
    
        if (!$observer = new $logger($config_args)) {
            throw new LoggerException('xarLog_init: Unable to instantiate class for logging: ' . $type);
        }

        $observer->start();
        self::$loggers[$type] = &$observer;
    }
}

/**
 * Shutdown handler for the logging system
 *
 * @package core\logging
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/
function xarLog__shutdown_handler()
{
     if (class_exists('xarAutoload')) {
         xarAutoload::$shutdown = true;
     }
     xarLog::message("xarLog: Running the shutdown handler", xarLog::LEVEL_NOTICE);
     if (!method_exists('xarSession', 'getId') || !method_exists('xarUser', 'getVar')) {
         xarLog::message("xarLog: Leaving session unexpectedly before session and user were defined", xarLog::LEVEL_NOTICE);
     } else{
         xarLog::message("xarLog: Leaving session: " . xarSession::getId() . " - User: " . xarUser::getVar('uname') . " (ID: " . xarUser::getVar('id') . ")", xarLog::LEVEL_NOTICE);
     }

     // If the debugger was active, we can dispose it now.
     if(xarDebug::$flags & xarConst::DBG_SQL) {
         xarLog::message("xarLog: Total SQL queries: $GLOBALS[xarDebug_sqlCalls].");
     }

     if (xarDebug::$flags & xarConst::DBG_ACTIVE) {
         $lmtime = explode(' ', microtime());
         $endTime = $lmtime[1] + $lmtime[0];
         $totalTime = ($endTime - xarDebug::$startTime);
         xarLog::message("xarLog: Response was served in $totalTime seconds.", xarLog::LEVEL_NOTICE);
     }

//During register_shutdown, it's already too late.
//fwrite presents problems during it.
//you can't use it with javascript/mozilla loggers...
//Maybe there should be a xaraya shutdown event?
/*
     xarLog::message("xarLog shutdown handler: Ending all logging.");

    foreach (array_keys($GLOBALS['xarLog_loggers']) as $id) {
       $GLOBALS['xarLog_loggers'][$id]->;
    }
 */
}

// Legacy calls - import by default for now...
//sys::import('xaraya.legacy.log');
