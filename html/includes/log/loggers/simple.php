<?php

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
* @package logging
*/

/**
 * Include the base file
 *
 */
include_once ('./includes/log/loggers/xarLogger.php');

/**
 * Simple logging class
 *
 * @package logging
 */
class xarLogger_simple extends xarLogger
{
    /** 
    * String holding the filename of the logfile. 
    * @var string
    */
    var $_filename;

    /**
    * Integer holding the file handle. 
    * @var integer
    */
    var $_fp;

    /**
    * Integer (in octal) containing the logfile's permissions mode.
    * @var integer
    */
    var $_mode = 0644;

    /**
    * Buffer holding what is to go to the file
    * @var array
    */
    var $_buffer;

    /**
    * Boolean which if true will mean
    * the lines are written out.
    */
    var $_writeOut;

    /**
    * Boolean which if true will mean
    * the file is open.
    */
    var $_isFileOpen;

    /**
    * Boolean which if true will mean
    * the file has already been opened,
    * assumes it wont disapear during a php execution.
    */
    var $_isFileWriteable;

    /**
    * Header to be inserted when creating the file
    */
    var $_fileheader;

    /**
    * Set up the configuration of the specific Log Observer.
    * 
    * @param  array $conf  with
    *               'fileName'     => string      The filename of the logfile.
    *               'maxLevel'     => int         Maximum level at which to log.
    *               'mode'         => string      File mode of te log file (optional)
    *               'timeFormat'   => string      Time format to be used in the file (optional)
    * @access public
    */
    function setConfig($conf)
    {
        parent::setConfig($conf);
        
        /* If a file mode has been provided, use it. */
        if (!empty($conf['mode'])) {
            $this->_mode = $conf['mode'];
        }

        $this->_buffer          = '--------------------------------------------------------------------------------------------------------------------------------------'."\r\n";
        $this->_writeOut        = true;
        $this->_isFileOpen      = false;
        $this->_isFileWriteable = false;
        $this->_fileheader      = '';
        
        $this->_filename        = $conf['fileName'];
        $this->_ensureFileWriteable();

        /* register the destructor */
        //Let's see if the destructors can work by themselves
        //This is not working, find out why later on
//      register_shutdown_function(array(&$this, '_destructor'));
    }
    
    /**
    * Destructor. This will write out any lines to the logfile, UNLESS the dontLog()
    * method has been called, in which case it won't.
    *
    * @access private
    */
    function _destructor()
    {
		//At this time, we can't send output to the screen,
		//fwrite doesnt seem to be working, how to know if
		//the destructor is being called?
		//Anyone with a nice debugger around?
        $this->writeOut();

        // Close the Log file
        $this->_closeLogfile();
    }

    /**
    * Updates the Observer, gets the actual State in the observable class and logs the message if it is appropriate
    *
    * @return boolean  True on success or false on failure.
    * @access public
    */
    function notify($message, $level)
    {
        // Abort early if the level of priority is above the maximum logging level.
        if (!$this->doLogLevel($level)) return false;

        // Add to loglines array
        $this->_buffer .= $this->_formatMessage($message, $level);
        
        //This shouldnt be necessary, fix afterwards
        //The destructor doesnt seem to be called, or
        //the script is not able to execute the fwrite(?) during shutdown
        $this->writeOut();

        return true;
    }
    
    /**
    * This function will prevent the destructor from logging.
    *
    * @access public
    */
    function dontLog()
    {
        $this->_writeOut = false;
    }

    /**
    * Function to force writing out of log *now*. Will clear the queue.
    * Using this function does not cancel the writeout in the destructor.
    * Handy for long running processes.
    *
    * @access public
    */
    function writeOut()
    {
        if (!empty($this->_buffer) AND ($this->_writeOut) AND $this->_openLogfile()) {
            fwrite($this->_fp, $this->_buffer);
            $this->_buffer = '';
        }
        
        if (!$this->_closeLogfile()) return false;
    }

    /**
    * Checks if the directory where the log file shoud be exists
    * then checks if the log file already exists,
    * if not then creates it.
    *
    * Sets $this->_filename to the file path then
    *
    * @param file $file Path to the logger file 
    * @access private
    */
	function _ensureFileWriteable() 
    {
        if (!file_exists($this->_filename) && 
            !is_writable(dirname($this->_filename))) {
       		die ('Logger file path given is not writeable: '.$this->_filename);
        }
        
        $this->_isFileWriteable = true;
        return true;
	}

    /**
    * Opens the logfile for appending. File should always exist, as
    * constructor will create it if it doesn't.
    *
    * @access private
    */
    function _openLogfile()
    {
        if ($this->_isFileOpen) {
            return true;
        }   // else {

		if (!$this->_isFileWriteable) {
			die('File is not writeable');
		}
		
		$insert_header = false;
		
		if (!file_exists($this->_filename)) {
			$insert_header = true;
		}
		
        if (($this->_fp = fopen($this->_filename, 'a')) == false) {
	        die('unable to open log file '.$this->_filename);
            return false;
        }  // else {

        if ($insert_header) {
        	fwrite($this->_fp, $this->_fileheader);
        }

	    $this->_filename = realpath($this->_filename);
        $this->_isFileOpen = true;
        return true;
    }
    
    /**
    * Closes the logfile file pointer.
    *
    * @access private
    */
    function _closeLogfile()
    {
        if (!fclose($this->_fp)) {
            return false;
        }
        
        $this->_isFileOpen = false;

        return true;
    }

    /**
    * Format a message to the logfile
    *
    * @param  string  $message  The line to write
    * @param  integer $level    The level of priority of this line/msg
    * @return integer           Number of bytes written or -1 on error
    * @access private
    */
    function _formatMessage($message, $level)
    {
        return sprintf("%s [%s] %s\r\n", $this->getTime(), $this->levelToString($level), $message);
    }
} // End of class
?>