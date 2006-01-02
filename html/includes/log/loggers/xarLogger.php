<?php

/*
Copyright (C) 2004 the Digital Development Corporation

The exception detailed below is granted for the following files in this directory:

- simple.php
- error_log.php
- mail.php
- sql.php
- syslog.php

As a special exception to the GNU General Public License Xaraya is distributed under, the Digital Development Corporation gives permission to link the code of this program with each of the files listed above (or with modified versions of each file that use the same license as the file), and distribute linked combinations including the two. You must obey the GNU General Public License in all respects for all of the code used other than each of the files listed above. If you modify this file, you may extend this exception to your version of the file, but you are not obligated to do so. If you do not wish to do so, delete this exception statement from your version.
*/

/**
 * This class implements the Logger
 *
 * @author  Flavio Botelho <nuncanada@ig.com.br>
 * @package logging
 */

/**
 * Base class for all loggers
 *
 * @package logging
 */
class xarLogger
{

    /**
    * The level of logging.
    *
    * The level of the messages which will be logged.
    */
    var $_logLevel;

    /**
    * Identity of the logger.
    *
    * Randomly generated to distinguish between 2 different logging processes,
    * in highly frequented sites, the time of the logged message isnt as good to diferenciate
    * different pageviews
    */
    var $_ident;

    /**
    * String containing the format to use when generating timestamps.
    * @var string
    */
    var $_timeFormat = '%b %d %H:%M:%S';

    // Elapsed time.
    var $_elapsed = 0;

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
        $this->_logLevel = $conf['logLevel'];

        $microtime = explode(" ", microtime());
        $this->_elapsed = ((float)$microtime[0] + (float)$microtime[1]);

/*
        // If no identity is given yet to this page view, then create it
        if (!isset($GLOBALS['_xar_logging_ident'])) {
            $GLOBALS['_xar_logging_ident'] = md5(microtime());
        }

        // Assigns the page view identity to be logged as the logger identity
        $this->_ident = $GLOBALS['_xar_logging_ident'];
*/
        $this->_ident = '';

        // If a custom time format has been provided, use it.
        if (!empty($conf['timeFormat'])) {
            $this->_timeFormat = $conf['timeFormat'];
        }
    }

    /**
     * Returns if the logger should log the given level or not.
     *
     * @param int $level        A XARLOG_LEVEL_* integer constant mix.
     * @return boolean         Should it be logger or not
     */
    function doLogLevel($level)
    {
        if ($level & $this->_logLevel) {
            return true;
        }

        return false;
    }

    /**
     * Returns the string representation of a XARLOG_LEVEL_* integer constant.
     *
     * @param int $level        A XARLOG_LEVEL_* integer constant.
     * @return string           The string representation of $level.
     */
    function levelToString($level)
    {
        static $levels = array(
            XARLOG_LEVEL_EMERGENCY => 'EMERGENCY',
            XARLOG_LEVEL_ALERT     => 'ALERT',
            XARLOG_LEVEL_CRITICAL  => 'CRITICAL',
            XARLOG_LEVEL_ERROR     => 'ERROR',
            XARLOG_LEVEL_WARNING   => 'WARNING',
            XARLOG_LEVEL_NOTICE    => 'NOTICE',
            XARLOG_LEVEL_INFO      => 'INFO',
            XARLOG_LEVEL_DEBUG     => 'DEBUG'
         );

        return $levels[$level];
    }

    function getTime()
    {
        $microtime = microtime();
        $microtime = explode(' ', $microtime);

        $secs = ((float)$microtime[0] + (float)$microtime[1]);

        return strftime($this->_timeFormat) . ' ' . $microtime[0] . ' +' . number_format(round($secs - $this->_elapsed, 3),3);
    }
}

?>