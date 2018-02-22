<?php
/**
 * @package core\logging
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/**
* The Log_file class is a concrete implementation of the Log::
* abstract class which writes message to a text file. This is based
* on the previous Log_file class by Jon Parise.
*
* @author  Richard Heyes <richard@php.net>
* @author  Nuncanada <nuncanada@ig.com.br>
*/

/**
 * Make sure the base class is available
 *
 */
sys::import('xaraya.log.loggers.xarLogger');

/**
 * The Log_syslog class is a concrete implementation of the Log::
 * abstract class which sends messages to syslog on UNIX-like machines
 * (PHP emulates this with the Event Log on Windows machines).
 * 
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.12 $
 * @since   Horde 1.3
 */
class xarLogger_syslog extends xarLogger 
{
    //Take a look at http://br.php.net/manual/en/function.openlog.php for the options/facilities

    /**
    * Integer holding the log facility to use.
    * @var int
    */
    var $_facility = LOG_USER;

    /**
    * Integer holding the log options to use.
    * @var int
    */
    var $_options = LOG_PID;

    /**
    * Boolean holding if the log was already open or not
    * @var bool
    */
    var $_opened = false;

    /**
     * Sets up the configuration specific parameters for each driver
     *
     * @param array     $conf               Configuration options for the specific driver.
     *
     * 
     * @return boolean
     */
    function setConfig(array &$conf) 
    {
        parent::setConfig($conf);
        
        /* If it is given a logging facility to be used, then use it. */
        if (isset($conf['facility'])) {
            $this->_facility = $conf['facility'];
        }

        /* If it is given a logging facility to be used, then use it. */
        if (isset($conf['options'])) {
            $this->_options = $conf['options'];
        }

        /* register the destructor */
        register_shutdown_function(array(&$this, '_destructor'));
    }

    /**
    * Destructor. This will write out any lines to the logfile, UNLESS the dontLog()
    * method has been called, in which case it won't.
    *
    * 
    */
    function _destructor()
    {
        $this->close();
    }

    /**
     * Opens a connection to the system logger, if it has not already
     * been opened.  This is implicitly called by log(), if necessary.
     * 
     */
    function open()
    {
        if (!$this->_opened) {
            openlog($this->_ident, $this->_options, $this->_facility);
            $this->_opened = true;
        }
    }

    /**
     * Closes the connection to the system logger, if it is open.
     *      
     */
    function close()
    {
        if ($this->_opened) {
            closelog();
            $this->_opened = false;
        }
    }

    /**
     * Sends $message to the currently open syslog connection.  Calls
     * open() if necessary. Also passes the message along to any Log_observer
     * instances that are observing this Log.
     * 
     * @param string $message  The textual message to be logged.
     * @param int $priority (optional) The priority of the message.  Valid
     *                  values are: PEAR_LOG_EMERG, PEAR_LOG_ALERT,
     *                  PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                  PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG.
     *                  The default is PEAR_LOG_INFO.
     * @return boolean  True on success or false on failure.
     *      
     */
    function notify($message, $level)
    {
        if (!$this->doLogLevel($level)) return false;

        if (!$this->_opened) {
            $this->open();
        }

        if (!syslog($this->_toSyslog($level), $message)) {
            return false;
        }

        return true;
    }

    /**
     * Converts a xarLog::LEVEL* constant into a syslog LOG_* constant.
     *
     * This function exists because, under Windows, not all of the LOG_*
     * constants have unique values.  Instead, the xarLog::LEVEL_* were introduced
     * for global use, with the conversion to the LOG_* constants kept local to
     * to the syslog driver.
     *
     * @param int $level     xarLog::LEVEL_* value to convert to LOG_* value.
     *
     * @return  The LOG_* representation of $priority.
     *
     * 
     */
    function _toSyslog($level)
    {
        static $levels = array(
            xarLog::LEVEL_EMERGENCY => LOG_EMERG,
            xarLog::LEVEL_ALERT     => LOG_ALERT,
            xarLog::LEVEL_CRITICAL  => LOG_CRIT,
            xarLog::LEVEL_ERROR     => LOG_ERR,
            xarLog::LEVEL_WARNING   => LOG_WARNING,
            xarLog::LEVEL_NOTICE    => LOG_NOTICE,
            xarLog::LEVEL_INFO      => LOG_INFO,
            xarLog::LEVEL_DEBUG     => LOG_DEBUG
        );

        return $levels[$level];
    }
}
?>