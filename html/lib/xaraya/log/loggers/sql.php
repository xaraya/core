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
 * SQL based logger
 *
 * @copyright see the html/credits.html file in this release
 *
*/

/**
 * Make sure the base class is available
 *
 */
sys::import('xaraya.log.loggers.xarLogger');
// Modified from the original by the Xaraya Team

/**
 * The Log_sql class is a concrete implementation of the Log::
 * abstract class which sends messages to an SQL server.  Each entry
 * occupies a separate row in the database.
 *
 * We can create this in 2 ways: create upon errors when trying to insert the data (creates on first use)
 * Create on activation of the logger module
 *
 *
 * CREATE TABLE `xar_log_messages` (
 *   `uuid` varchar(32) NOT NULL,
 *   `logtime` varchar(255) NOT NULL DEFAULT '',
 *   `priority` tinyint(4) NOT NULL DEFAULT '0',
 *   `message` text NOT NULL
 *   PRIMARY KEY  (`id`)
 * );
 *
 * @author  Jon Parise <jon@php.net>
 * @version $Revision: 1.21 $
 * @since   Horde 1.3
 */
class xarLogger_sql extends xarLogger
{
    /**
     * String holding the database table to use.
     * @var string
     */
    private $sqltable;
    private $buffer;

    /**
     * Pointer holding the database connection to be used.
     * @var string
     */
    private $dbconn;

    /**
    * Set up the configuration of the specific Log Observer.
    *
    * @param  array $conf  with
    *               'sqltable  '     => string      The name of the logger table.
    * 
    */
    public function __construct(Array $conf)
    {
        parent::__construct($conf);
        
        if (!empty($conf['sqltable'])) {
	        $this->sqltable = $conf['sqltable'];
        }

        // Initialise the buffer
        $this->buffer = array();
    }

    public function close()
    {
        parent::close();

        // Create the database connection
        $this->dbconn = xarDB::getConn();
        
        // Write the records to the database and stop logging.
        foreach ($this->buffer as $line) {
        	$line = explode('|||', $line);

			/* Build the SQL query for this log entry insertion. */
			$q = sprintf('INSERT INTO %s (uuid, logtime, priority, message)' .
						 'VALUES(?, ?, ?, ?)',
						 $this->sqltable);
			$bindvars = array($this->uuid, $line[0], $line[1], $line[2]);
			$stmt = $this->dbconn->prepareStatement($q);
			$stmt->executeUpdate($bindvars);
        }
    }

    /**
     * Inserts $message to the currently open database.  Calls open(),
     * if necessary.  Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param string $message  The textual message to be logged.
     * @param string $priority The priority of the message.  Valid
     *                  values are: PEAR_LOG_EMERG, PEAR_LOG_ALERT,
     *                  PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                  PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG.
     *                  The default is PEAR_LOG_INFO.
     * @return boolean  True on success or false on failure.
     * 
     */
    public function notify($message, $level)
    {
        // Abort early if the level of priority is above the maximum logging level.
        if (!$this->doLogLevel($level)) return false;

        // Add to the loglines array
        $this->buffer[] = $this->formatMessage($message, $level);

        return true;
    }
    
    public function formatMessage($message, $level)
    {
        return $this->getTime() . '|||' . $level . '|||' . $message;
    }
}
