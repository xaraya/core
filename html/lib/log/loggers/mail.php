<?php

// Modified by Xaraya Team

/**
 * The Log_mail class is a concrete implementation of the Log:: abstract class
 * which sends log messages to a mailbox.
 * The mail is actually sent when you close() the logger, or when the destructor
 * is called (when the script is terminated).
 * 
 * PLEASE NOTE that you must create a Log_mail object using =&, like this :
 *  $logger =& Log::factory("mail", "recipient@example.com", ...)
 * 
 * This is a PEAR requirement for destructors to work properly.
 * See http://pear.php.net/manual/en/class.pear.php
 * 
 * @author  Ronnie Garcia <ronnie@mk2.net>
 * @author  Jon Parise <jon@php.net>
 * @version $Revision: 1.8 $
 * @package logging
 */
 
/**
 * Make sure the base class is available
 *
 */
sys::import('log.loggers.xarLogger');

/**
 * Mail logger
 *
 * @package logging
 */
class xarLogger_mail extends xarLogger 
{

    /** 
     * String holding the recipient's email address.
     * @var string
     */
    var $_recipient = '';

    /** 
     * String holding the sender's email address.
     * @var string
     */
    var $_from = '';

    /** 
     * String holding the email's subject.
     * @var string
     */
    var $_subject = '[Log_mail] Log message';

    /**
     * String holding the mail message body.
     * @var string
     */
    var $_message = '';

    /**
     * @var boolean Holds wether the message was already opened or not.
     */
    var $_opened = false;


    /**
     * Constructs a new Log_mail object.
     * 
     * @param array  $conf      The configuration array.
     * Obligatory configurations:
     *   $conf['to']        : The e-mail that will be receiving the log files.
     *
     * Optional configurations:
     *   $conf['$maxLevel'] : Maximum level at which to log.
     *   $conf['from']      : the mail's "From" header line,
     *   $conf['subject']   : the mail's "Subject" line.
     * 
     * @access public
     */
    function setConfig(&$conf)
    {
        parent::setConfig($conf);

        $this->_recipient = $conf['to'];

        if (!empty($conf['from'])) {
            $this->_from = $conf['from'];
        } else {
            $this->_from = ini_get('sendmail_from');
        }
        
        if (!empty($conf['subject'])) {
            $this->_subject = $conf['subject'];
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
     * Starts a new mail message.
     * This is implicitly called by log(), if necessary.
     * 
     * @access public
     */
    function open()
    {
        if (!$this->_opened) {
            $this->_message = "Log messages:\n\n";
            $this->_opened = true;
        }
    }

    /**
     * Closes the message, if it is open, and sends the mail.
     * This is implicitly called by the destructor, if necessary.
     * 
     * @access public
     */
    function close()
    {
        if ($this->_opened) {
            if (!empty($this->_message)) {
                $headers = "From: $this->_from\r\n";
                $headers .= "User-Agent: Log_mail\r\n";

                if (mail($this->_recipient, $this->_subject, $this->_message,
                        $headers, "-f".$this->_from) == false) {
                    //FIXME: Use xarLogMessage, with an extra variable to rule this 
                    // logger out and make it log on the others avaiable
                    error_log("Log_mail: Failure executing mail()", 0);
                    return false;
                }
            }
            $this->_opened = false;
        }

        return true;
    }

    /**
     * Writes $message to the currently open mail message.
     * Calls open(), if necessary.
     * 
     * @return boolean  True on success or false on failure.
     * @access public
     */
    function notify($message, $level)
    {
        if (!$this->doLogLevel($level)) return false;

        if (!$this->_opened) {
            $this->open();
        }

        $entry = sprintf("%s %s [%s] %s\n", $this->getTime(),
            $this->_ident, $this->levelToString($level), $message);

        $this->_message .= $entry;
        
        return true;
    }
}

?>