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
      *               'filename'     => string      The filename of the logfile.
      *               'maxLevel'     => int         Maximum level at which to log.
      *               'mode'         => string      File mode of te log file (optional)
      *               'timeFormat'   => string      Time format to be used in the file (optional)
      * 
     **/
    public function __construct(Array $conf)
    {
        parent::__construct($conf);
		
		$this->header = '<?xml version="1.0" encoding="utf-8"?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
            <head><title>Xaraya HTML Logger</title></head>
            <body><br />
                <table border="1" width="100%" cellpadding="2" cellspacing="0">
                    <tr align="center">
                        <th style="width: 250px">Time</th>
                        <th style="width: 80px">Logging Level</th>
                        <th>Message</th>
                    </tr>';
    }
    
    /**
      * Start the logger
      *
      * Begin filling the buffer and ready the log file for writing
      * 
     **/
    public function start()
    {
		$this->buffer = $this->header;
         
        // Write the request details.
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->buffer .= '<tr style="background-color:#e3e3e3;"><td colspan="3">REQUEST_URI: '.$_SERVER["REQUEST_URI"].'</td></tr>';
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->buffer .= '<tr style="background-color:#e3e3e3;"><td colspan="3">HTTP_REFERER: '.$_SERVER["HTTP_REFERER"].'</td></tr>';
        }

        // Set the log file up for writing.
        $this->prepareLogfile();
    }

    public function close()
    {
        xarLogger::close();

		$this->buffer .= '</tr>' . $this->EOL;
		$this->buffer .= '<tr>
							<td colspan="3">HTTP_REFERER: ' . $_SERVER['HTTP_REFERER'] . '</td>
							</tr>
							</table>
							</body>
							</html>';

        // Flush any remaining records and stop logging.
        $this->flushBuffer(true);
    }
    /**
     * Writes a line to the logfile
     *
     * @param  string  $message   The line to write
     * @param  integer $level     The level of priority of this line/msg
     * 
    **/
    public function formatMessage($message, $level)
    {
        return sprintf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>",
                                     $this->getTime(),
                                     self::$levels[$level],
                                     nl2br(htmlspecialchars($message)));
    }
}

?>