<?php

/**
 * File: $Id$
 *
 * SQL based logger
 *
 * @package logging
 * @copyright (C) 2003 by the Xaraya Development Team.
 * 
*/

/**
 * Include the base class
 *
 */
include_once ('./includes/log/loggers/xarLogger.php');
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
 * CREATE TABLE log_table (
 *  id          INT NOT NULL,
 *  ident       VARCHAR(32) NOT NULL,
 *  logtime     TIMESTAMP NOT NULL,
 *  priority    TINYINT NOT NULL,
 *  message     TINYTEXT
 * );
 *
 * @author  Jon Parise <jon@php.net>
 * @version $Revision: 1.21 $
 * @since   Horde 1.3
 * @package logging
 */
class xarLogger_sql extends xarLogger
{
    /**
     * String holding the database table to use.
     * @var string
     */
    var $_table;

    /**
     * Pointer holding the database connection to be used.
     * @var string
     */
    var $_dbconn;

    /**
    * Set up the configuration of the specific Log Observer.
    *
    * @param  array $conf  with
    *               'table  '     => string      The name of the logger table.
    * @access public
    */
    function setConfig($conf)
    {
        parent::setConfig($conf);

        $this->_dbconn =& xarDBGetConn();

        $this->_table = $conf['table'];
//        $xartable =& xarDBGetTables();
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
     * @access public     
     */
    function notify($message, $priority)
    {
        if (!$this->doLogLevel($level)) return false;

        /* Build the SQL query for this log entry insertion. */
        $id = $this->_dbconn->GenId('log_id');
        $q = sprintf('insert into %s (id, ident, logtime, priority, message)' .
                     'values(?, ?, ?, ?, ?)',
                     $this->_table);
        $bindvars = array($id, $thid->_ident, $this->getTime(), $priority, $message);
        $stmt =& $this->_dbconn->prepareStatement($q);
        $result =& $stmt->executeUpdate($bindvars);
        if (!$result) {
            return false;
        }

        return true;
    }
}
?>