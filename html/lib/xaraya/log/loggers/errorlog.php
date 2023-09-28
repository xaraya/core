<?php
// $Id: syslog.php,v 1.12 2003/04/08 05:55:05 jon Exp $
// $Horde: horde/lib/Log/syslog.php,v 1.6 2000/06/28 21:36:13 jon Exp $

/**
 * @package core\logging
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
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
 * The error_log class is an implementation of the xarLoggger
 * abstract class which sends messages to to the web server's error log, a TCP port or to a file.
 *
 * @author  Flavio Botelho <nuncanada@xaraya.com>
 */
class xarLogger_errorlog extends xarLogger
{
    //Take a look at http://br.php.net/manual/en/function.error_log.php

    /**
    * Integer holding the log facility to use.
    * @var int
    */
    private $logtype = 0; //SYSTEM_LOG = 0, TCP_LOG = 1, FILE_LOG = 2, MAIL_LOG = 3

    /**
    * String holding destination of the logged message.
    * @var string
    */
    // Not needed for now. Use the simple logger
    //private $destination;

    /**
    * String hold extra headers in case of using the mail logger.
    * @var int
    */
    // Used only with emails. Not needed for now. Use the mail logger
    // private $extra_headers;

    /**
     * Sets up the configuration specific parameters for each driver
     *
     * @param array<string, mixed> $conf               Configuration options for the specific driver.
     *
     * 
     * @return boolean
     */
    public function __construct(Array $conf)
    {
        parent::__construct($conf);

        /* If it is given a destination, then use it. */
        /*
        if (!empty($conf['destination'])) {
            $this->destination = $conf['destination'];
        }
		*/
        /* If it is given a logging type to be used, then use it. */
        //This should be useful only when 0.
        //The rest of the options will have better coverage from other loggers.
        /*
        if (!empty($conf['type'])) {
            $this->type = $conf['type'];
        }
		*/
        /* If it is given a logging type to be used, then use it. */
        /*
        if (!empty($conf['extra_headers'])) {
            $this->extra_headers = $conf['extra_headers'];
        }
        */
    }

    /**
     * Sends $message to the currently open syslog connection.  Calls
     * open() if necessary. Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param string $message  The textual message to be logged.
     * @param int $level (optional) The priority of the message.  Valid
     *                  values are: PEAR_LOG_EMERG, PEAR_LOG_ALERT,
     *                  PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                  PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG.
     *                  The default is PEAR_LOG_INFO.
     * @return boolean  True on success or false on failure.
     * 
     */
    function notify($message, $level)
    {
        // Abort early if the level of priority is above the maximum logging level.
        if (!$this->doLogLevel($level)) return false;

        $entry = sprintf("%s %s [%s] %s\n", $this->getTime(),
            $this->uuid, self::$levels[$level], $message);

        // if (!error_log($entry, $this->logtype, $this->destination, $this->extra_headers)) {
        if (!error_log($entry, $this->logtype)) {
            return false;
        }
        return true;
    }
}
