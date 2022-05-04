<?php
/*
 * @copyright see the html/credits.html file in this release

    The exception detailed below is granted for the following files in this 
    directory:

    - simple.php
    - error_log.php
    - mail.php
    - sql.php
    - syslog.php

    As a special exception to the GNU General Public License Xaraya is distributed 
    under, the Digital Development Corporation gives permission to link the code of 
    this program with each of the files listed above (or with modified versions of 
    each file that use the same license as the file), and distribute linked 
    combinations including the two. You must obey the GNU General Public License 
    in all respects for all of the code used other than each of the files listed 
    above. If you modify this file, you may extend this exception to your version 
    of the file, but you are not obligated to do so. If you do not wish to do so, 
    delete this exception statement from your version.
*/

/**
 * Base class for all loggers
 *
 * @package core\logging
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author  Flavio Botelho <nuncanada@ig.com.br>
 */
class xarLogger extends xarObject
{
    /**
    * The array of logging levels
    */
	static public $levels = array(
		xarLog::LEVEL_EMERGENCY => 'EMERGENCY',
		xarLog::LEVEL_ALERT     => 'ALERT',
		xarLog::LEVEL_CRITICAL  => 'CRITICAL',
		xarLog::LEVEL_ERROR     => 'ERROR',
		xarLog::LEVEL_WARNING   => 'WARNING',
		xarLog::LEVEL_NOTICE    => 'NOTICE',
		xarLog::LEVEL_INFO      => 'INFO',
		xarLog::LEVEL_DEBUG     => 'DEBUG'
	 );
    /**
    * The level of logging.
    *
    * The level of the messages which will be logged.
    */
    protected $logLevel;

    /**
    * Identity of the logger.
    *
    * Randomly generated to distinguish between 2 different logging processes,
    * in highly frequented sites, the time of the logged message isnt as good to diferentiate
    * different pageviews
    */
    protected $uuid;

    /**
    * String containing the format to use when generating timestamps.
    * @var string
    */
    // Note: before changing this, check windows support for the specifiers
    protected $timeFormat = '%b %d %H:%M:%S';

    // Elapsed time.
    protected $elapsed = 0;

    /**
     * Sets up the configuration specific parameters for each driver
     *
     * @param array     $conf               Configuration options for the specific driver.
     *
     * @return boolean
     */
    public function __construct(Array $conf)
    {
        if ($conf['fallback'] == true) {
        	// The levels defined in the system configuration file
			$levels = isset($conf['level']) ? $conf['level'] : xarSystemVars::get(sys::CONFIG, 'Log.Level');
        } else {
        	// The levels defined in the log configuration file
			$levels = isset($conf['level']) ? $conf['level'] : xarSystemVars::get(sys::LOG, 'Log.' . ucwords($conf['type']) . '.Level');
        }
		if (!empty($levels)) {
			$this->logLevel = 0;
			$levels = explode(',', $levels);
			foreach ($levels as $level) $this->logLevel |= (int)$level;
		} else {
			$this->logLevel = xarLog::LEVEL_ALL;
		}

        $microtime = explode(" ", microtime());
        $this->elapsed = ((float)$microtime[0] + (float)$microtime[1]);

        // Create a UUID
        $this->uuid = bin2hex(random_bytes(16));

        // If a custom time format has been provided, use it.
        if (!empty($conf['timeFormat'])) {
            $this->timeFormat = $conf['timeFormat'];
        }
    }

    /**
      * Start the logger
      *
      * This method gets overwritten
     **/
    public function start()
    {
    }
    
    /**
    * Destructor. This calls the logger specific close function
    *
    */
    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        // This is overwritten by the subclasses
    }

    /**
     * Returns if the logger should log the given level or not.
     *
     * @param int $level        A xarLog::$LEVEL_* integer constant mix.
     * @return boolean         Should it be logger or not
     */
    function doLogLevel($level)
    {
        if ($level & $this->logLevel) {
            return true;
        }
        return false;
    }

    function getTime()
    {
        $microtime = microtime();
        $microtime = explode(' ', $microtime);

        $secs = ((float)$microtime[0] + (float)$microtime[1]);
        // NOTE: when using E_STRICT, and PHP has no 'own' timezone setting
        // strftime() will issue notices on that. But that's what you get with
        // E_STRICT ;-) so we will leave this.  
        return strftime($this->timeFormat) . ' ' . $microtime[0] . ' +' . number_format(round($secs - $this->elapsed, 3),3);
    }
}

?>