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
 */
 
/**
 * Make sure the base class is available
 *
 */
sys::import('xaraya.log.loggers.xarLogger');

/**
 * Mail logger
 *
 */
class xarLogger_mail extends xarLogger 
{
    /** 
     * String holding the recipient's email address.
     * @var string
     */
    private $recipient = '';

    /** 
     * String holding the sender's email address.
     * @var string
     */
    private $sender = '';

    /** 
     * String holding the email's subject.
     * @var string
     */
    private $subject = '[Log_mail] Log message';

    /**
     * String holding the mail message body.
     * @var string
     */
    private $message = '';

    /**
     * @var boolean Holds wether the message was already opened or not.
     */
    private $opened = false;


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
     * 
     */
    public function setConfig(Array &$conf)
    {
        parent::setConfig($conf);

        $this->recipient = $conf['recipient'];

        if (!empty($conf['sender'])) {
            $this->sender = $conf['sender'];
        } else {
            $this->sender = ini_get('sendmail_from');
        }
        
        if (!empty($conf['subject'])) {
            $this->subject = $conf['subject'];
        }

        /* register the destructor */
        register_shutdown_function(array(&$this, 'destructor'));
    }

    /**
    * Destructor. This will write out any lines to the logfile, UNLESS the dontLog()
    * method has been called, in which case it won't.
    *
    * 
    */
    public function destructor()
    {
        $this->close();
    }

    /**
     * Starts a new mail message.
     * This is implicitly called by log(), if necessary.
     * 
     * 
     */
    public function open()
    {
        if (!$this->opened) {
            $this->message = "Log messages:\n\n";
            $this->opened = true;
        }
    }

    /**
     * Closes the message, if it is open, and sends the mail.
     * This is implicitly called by the destructor, if necessary.
     * 
     * 
     */
    public function close()
    {
        if ($this->opened) {
            if (!empty($this->message)) {
                $headers = "From: $this->sender\r\n";
                $headers .= "User-Agent: Log_mail\r\n";

                if (mail($this->recipient, $this->subject, $this->message,
                        $headers, "-f".$this->sender) == false) {
                    //FIXME: Use xarLog::message, with an extra variable to rule this 
                    // logger out and make it log on the others avaiable
                    error_log("Log_mail: Failure executing mail()", 0);
                    return false;
                }
            }
            $this->opened = false;
        }

        return true;
    }

    /**
     * Writes $message to the currently open mail message.
     * Calls open(), if necessary.
     * 
     * @return boolean  True on success or false on failure.
     * 
     */
    public function notify($message, $level)
    {
        if (!$this->doLogLevel($level)) return false;

        if (!$this->opened) {
            $this->open();
        }

        $entry = sprintf("%s %s [%s] %s\n", $this->getTime(),
            $this->ident, $this->levels[$level], $message);

        $this->message .= $entry;
        
        return true;
    }
}

?>