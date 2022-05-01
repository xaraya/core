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

// Modified by the xaraya Team

// +-----------------------------------------------------------------------+
// | Copyright (c) 2002-2003  Richard Heyes                                |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// |         Jon Parise <jon@php.net>                                      |
// +-----------------------------------------------------------------------+
//
// $Id: file.php,v 1.22 2003/04/08 06:37:42 jon Exp $

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
 */
sys::import('xaraya.log.loggers.xarLogger');

/**
 * Simple logging class
 *
 */
class xarLogger_simple extends xarLogger
{
    // String holding the filename of the logfile.
    // @var string
    private $filename;

    // Integer holding the file handle.
    // NULL if the file is not open.
    // @var integer
    private $fp = NULL;

    // Integer containing the logfile's permissions mode.
    // Written in octal, the permissions mimic the Unix 'chmod' format.
    // If zero, then no changes are made to the file mode when created.
    // Typical value: 0644
    // @var integer
    private $mode = 0;

    // Output buffer. Log records are buffered before being written to
    // the log file either explicitly, or on destroying the class.
    // @var array
    private $buffer;

    // Maximum file size
    // TODO: allow formats such as '2M', '100k' etc. That conversion could
    // be a core function, as there are many places it could be used.
    private $maxFileSize = 5000000; // 5Mb

    // End of line marker for writing to the log file.
    // TODO: automatically determine the OS-specific EOL characters.
    public $EOL = "\r\n";

    // Configure the logging object.
    // @param $conf['fileName'] string The filename of the logfile
    // @param $conf['mode'] string File mode of the log file, in Octal (optional)
    // @param $conf['maxFileSize'] integer The maximum size the logfile can be before it is moved or deleted (optional, bytes)
    // 
    public function setConfig(array &$conf)
    {
        parent::setConfig($conf);

        // If a file mode has been provided, use it.
        // Note the mode is passed in as an Octal string.
        if (!empty($conf['mode'])) {
            $this->mode = octdec((string)$conf['mode']);
        }

        // If a maximum size has been supplied, use it.
        if (!empty($conf['maxfilesize'])) {
            $this->maxFileSize = $conf['maxfilesize'];
        }

        // Start with a horizontal rule.
        $this->buffer = str_repeat('-', 79) . $this->EOL;

        // Write the request details.
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->buffer .= 'REQUEST_URI: ' . $_SERVER['REQUEST_URI'] . $this->EOL;
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->buffer .= 'HTTP_REFERER: ' . $_SERVER['HTTP_REFERER'] . $this->EOL;
        }

        $this->filename = $conf['filename'];

        // Set the log file up for writing.
        $this->prepareLogfile();

        // Register the destructor.
        // Can't do this, it will miss out on the logging of the other subsystems
        //register_shutdown_function(array(&$this, '_xarLogger_simple_destructor'));
    }

    // Destructor. This will write outstanding records to the logfile.
    // 
    public function _xarLogger_simple_destructor()
    {
        // Push a final message to the log.
        $this->notify('Shutdown simple logger', xarLog::LEVEL_DEBUG);

        // Flush any remaining records and stop logging.
        $this->flushBuffer(true);
    }

    // Add a message, applying appropriate formatting, to the output buffer.
    // @return boolean true on success or false on failure.
    // 
    public function notify($message, $level)
    {
        // Abort early if the level of priority is above the maximum logging level.
        if (!$this->doLogLevel($level)) {
            return false;
        }

        // Add to loglines array
        $this->buffer .= $this->formatMessage($message, $level);
        $this->flushBuffer(false);
        return true;
    }

    // Clear the output buffer (and optionally stop logging).
    // 
    public function clearBuffer($stop_logging=false)
    {
        $this->buffer = '';

        if ($stop_logging) {
            $this->filename = NULL;
        }
    }

    // Flush the current buffer to the log file (and optionally stop logging).
    // Handy for long running processes.
    // 
    public function flushBuffer($stop_logging=false)
    {
        if (!empty($this->buffer) && $this->openLogfile()) {
            fwrite($this->fp, $this->buffer);
            $this->buffer = '';
        }

        // Close the log file.
        // It will be opened again if further records need to be written.
        $this->closeLogfile();

        if ($stop_logging) {
            $this->filename = NULL;
        }
    }

    /**
     * Prepare the logfile for writing
     *
     * @param file $file Path to the logger file
     * 
     * @throws LoggerException
     * @return boolean true on success
     **/
    private function prepareLogfile()
    {
        if (file_exists($this->filename)) {
            if (!is_writable($this->filename)) {var_dump($this->filename);
                $err = error_get_last();
                throw new LoggerException('Unable to write to logger file: ' . $this->filename
                    .  ' (' . $err['message'] . ')' );
            }
        } else {
            if (!is_writable(dirname($this->filename))) {
                throw new LoggerException('Logger directory is not writeable: ' . dirname($this->filename));
            }

            $this->newLogFile();
        }

        // The realpath() function requires a file to exist before it will work,
        // so it can not be applied earlier than this point.
        $this->filename = realpath($this->filename);

        return true;
    }

    /**
     * Open the logfile for writing.
     *
     * The file should always exist, as setConfig() will have created it
     * if it did not
     *
     * @throws LoggerException
     * @return boolean true on succes, false on failure
     **/
    private function openLogfile()
    {
        // Log file is already open.
        if (!empty($this->fp)) {
            return true;
        }

        // The logger has aleady been shut down.
        if (empty($this->filename)) {
            return false;
        }

        // The config stage will have ensured the file exists and is
        // writable. If the file has exceeded its max size, then
        // start a new logfile.
        if (filesize($this->filename) > $this->maxFileSize) {
            // Start a new logfile.
            $this->newLogFile();
        }

        // Always append - the will be a log file ready.
        if (($this->fp = @fopen($this->filename, 'a')) == false) {
            $err = error_get_last();
            throw new LoggerException('Unable to open log file for writing: ' . $this->filename
                . ' (' . $err['message']. ')');
        }

        return true;
    }

    // Closes the logfile, if open.
    // @return boolean True if the log file is (or was) closed, false if not
    // 
    private function closeLogfile()
    {
        if (empty($this->fp)) {
            return true;
        }
        if (!fclose($this->fp)) {
            return false;
        }
        $this->fp = NULL;
        return true;
    }

    // Create the log file if it does not exist.
    // In here, truncate the file if it already exists.
    // TODO: if a non-empty file exists, rename it with some
    // timestamp or sequential suffix.
    private function newLogFile()
    {
        if (!file_exists($this->filename)) {
            // Create a new file.
            touch($this->filename);
			if (!empty($this->mode)) {
				// Set the default mode for the file.
				chmod($this->filename, $this->mode);
			}
        } else {
            if (filesize($this->filename) > 0) {
            // File exists and is not empty. Rename it and create a new, empty one
				$newname = $this->filename . "_" . time();
				rename($this->filename, $newname);
				touch($this->filename);
				if (!empty($this->mode)) {
					// Set the default mode for the file.
					chmod($this->filename, $this->mode);
				}
            }
        }
    }

    // Format a message.
    // @param string $message The message detail text
    // @param integer $level The priority level of this record
    // @return string The formatted log record
    // 
    private function formatMessage($message, $level)
    {
        return $this->getTime() . ' [' . $this->levels[$level] . '] ' . $message . $this->EOL;
    }

    // Get the name of the file to which we are writing.
    // @return string The file name
    // 
    public function getFilename()
    {
        return basename($this->filename);
    }
}

?>