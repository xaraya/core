<?php

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
class xarLogger {

    /**
    * The maximum level of logging.
    *
    * This will be changed to a fixed level later on
    * look at xarVar.php defines, they define the level of logging here.
    */
    var $_maxLevel;

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
        $this->_maxLevel = $this->stringToLevel($conf['maxLevel']);

        /* If no identity is given yet to this page view, then create it */
        if (!isset($GLOBALS['_xar_logging_ident'])) {
            $GLOBALS['_xar_logging_ident'] = md5(microtime());
        }

        /* Assigns the page view identity to be logged as the logger identity*/
        $this->_ident = $GLOBALS['_xar_logging_ident'];

        /* If a custom time format has been provided, use it. */
        if (!empty($conf['timeFormat'])) {
            $this->_timeFormat = $conf['timeFormat'];
        }
    }

    /**
     * Returns the string representation of a XARLOG_LEVEL_* integer constant.
     *
     * @param int $level        A XARLOG_LEVEL_* integer constant.
     *
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

    /**
     * Returns the defined integer representation of a string from the configuration.
     *
     * @param string $string   One of the priority level strings.
     *
     * @return string           The string representation of $level.
     */
    function stringToLevel($string)
    {
        static $strings = array (
            'EMERGENCY' => XARLOG_LEVEL_EMERGENCY,
            'ALERT'     => XARLOG_LEVEL_ALERT,
            'CRITICAL'  => XARLOG_LEVEL_CRITICAL,
            'ERROR'     => XARLOG_LEVEL_ERROR,
            'WARNING'   => XARLOG_LEVEL_WARNING,
            'NOTICE'    => XARLOG_LEVEL_NOTICE,
            'INFO'      => XARLOG_LEVEL_INFO,
            'DEBUG'     => XARLOG_LEVEL_DEBUG
        );

        return $strings[$string];
    }
    
    function getTime()
    {
        $microtime = microtime();
        $microtime = explode(' ', $microtime);
        return strftime($this->_timeFormat).' '.$microtime[0];
    }
}

?>