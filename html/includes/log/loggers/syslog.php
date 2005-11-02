<?php
// $Id: syslog.php,v 1.12 2003/04/08 05:55:05 jon Exp $
// $Horde: horde/lib/Log/syslog.php,v 1.6 2000/06/28 21:36:13 jon Exp $

/**
* The Log_file class is a concrete implementation of the Log::
* abstract class which writes message to a text file. This is based
* on the previous Log_file class by Jon Parise.
*
* @author  Richard Heyes <richard@php.net>
* @author  Nuncanada <nuncanada@ig.com.br>
* @package logging
*/

/**
 * Include the base file
 *
 */
include_once ('./includes/log/loggers/xarLogger.php');

/**
 * The Log_syslog class is a concrete implementation of the Log::
 * abstract class which sends messages to syslog on UNIX-like machines
 * (PHP emulates this with the Event Log on Windows machines).
 * 
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.12 $
 * @since   Horde 1.3
 * @package logging
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
     * @access public
     * @return boolean
     */
    function setConfig(&$conf) 
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
    * @access private
    */
    function _destructor()
    {
        $this->close();
    }

    /**
     * Opens a connection to the system logger, if it has not already
     * been opened.  This is implicitly called by log(), if necessary.
     * @access public
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
     * @access public     
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
     * @access public     
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
     * Converts a XARLOG_LEVEL* constant into a syslog LOG_* constant.
     *
     * This function exists because, under Windows, not all of the LOG_*
     * constants have unique values.  Instead, the XARLOG_LEVEL_* were introduced
     * for global use, with the conversion to the LOG_* constants kept local to
     * to the syslog driver.
     *
     * @param int $level     XARLOG_LEVEL_* value to convert to LOG_* value.
     *
     * @return  The LOG_* representation of $priority.
     *
     * @access private
     */
    function _toSyslog($level)
    {
        static $levels = array(
            XARLOG_LEVEL_EMERGENCY => LOG_EMERG,
            XARLOG_LEVEL_ALERT     => LOG_ALERT,
            XARLOG_LEVEL_CRITICAL  => LOG_CRIT,
            XARLOG_LEVEL_ERROR     => LOG_ERR,
            XARLOG_LEVEL_WARNING   => LOG_WARNING,
            XARLOG_LEVEL_NOTICE    => LOG_NOTICE,
            XARLOG_LEVEL_INFO      => LOG_INFO,
            XARLOG_LEVEL_DEBUG     => LOG_DEBUG
        );

        return $levels[$level];
    }
}
?>