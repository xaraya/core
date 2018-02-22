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
 * The HTML logger
 *
 * @copyright see the html/credits.html file in this release
 * 
*/

/**
 * Simple logger is the parent class
 *
 */
sys::import('xaraya.log.loggers.simple');

/**
 * HTMLLoggger
 *
 * Implements a logger to a HTML file
 *
 */
class xarLogger_html extends xarLogger_simple
{
    /**
      * Set up the configuration of the specific Log Observer.
      *
      * @param  array $conf  with
      *               'name'         => string      The filename of the logfile.
      *               'maxLevel'     => int         Maximum level at which to log.
      *               'mode'         => string      File mode of te log file (optional)
      *               'timeFormat'   => string      Time format to be used in the file (optional)
      * 
     **/
     function setConfig (Array &$conf) 
     {
         parent::setConfig($conf);
         $this->_fileheader = '<?xml version="1.0" encoding="utf-8"?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
            <head><title>Xaraya HTML Logger</title></head>
            <body><br />
                <table border="1" width="100%" cellpadding="2" cellspacing="0">
                    <tr align="center">
                        <th>Time</th>
                        <th>Logging Level</th>
                        <th>Message</th>
                    </tr>';
        $this->_buffer     = "\r\n".'<tr style="background-color:#e3e3e3;"><th>New Page View</th><th colspan="2">'.$_SERVER["REQUEST_URI"].'</th></tr>';
    }
    
    /**
     * Writes a line to the logfile
     *
     * @param  string  $message   The line to write
     * @param  integer $level     The level of priority of this line/msg
     * 
    **/
    function _formatMessage($message, $level)
    {
        return sprintf("\r\n<tr align=\"center\"><td>%s</td><td>%s</td><td>%s</td></tr>",
                                     $this->getTime(),
                                     $this->levelToString($level),
                                     nl2br(htmlspecialchars($message)).'<br />');
    }
}

?>