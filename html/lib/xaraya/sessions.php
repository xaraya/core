<?php
/**
 * Session Support
 *
 * @package core\sessions
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Michel Dalle
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo We have to define a public interface so NOWHERE ever anyone else touches anything related to the session implementation
 */

/**
 * Session exception class
 *
 */
class SessionException extends Exception
{}

/**
 * Class to model the default session handler
 *
 *
 * @todo this is a temp, since the obvious plan is to have a factory here
 */
interface IsessionHandler
{
    public function register($ipAddress);
    public function start();
    public function id($id = null);
    public function isNew();
    public function current();

    public function open($path, $name);
    public function close();
    public function read($sessionId);
    public function write($sessionId, $vars);
    public function destroy($sessionId);
    public function gc($maxlifetime);
}

/**
 * Session Support
 *
 * @package core\sessions
 */
class xarSession extends xarObject implements IsessionHandler
{
    const  PREFIX='XARSV';     // Reserved by us for our session vars
    const  COOKIE='XARAYASID'; // Our cookiename
    /** @var ?int */
    public static $anonId = null;     // Replacement for _XAR_ID_UNREGISTERED
    private static $securityLevel;
    private static $duration;
    private static $inactivityTimeout;
    //private static $cookieName;
    //private static $cookiePath;
    //private static $cookieDomain;
    //private static $refererCheck;
    private static $instance;
    private static $lastSaved;
    private $db;               // We store sessioninfo in the database
    private $tbl;              // Container for the session info
    private $isNew = true;     // Flag signalling if we're dealing with a new session

    private $sessionId = null; // The id assigned to us.
    private $ipAddress = '';   // IP-address belonging to this session.

    /**
     * Constructor for the session handler
     *
     * @return void
     * @throws SessionException
     **/
    function __construct(&$args)
    {
        // Set up our container.
        $this->db = xarDB::getConn();
        $tbls     = xarDB::getTables();
        $this->tbl = $tbls['session_info'];

        // Put a reference to this instance into a static property
        self::$instance = $this;
        
        // Set up the environment
        $this->setup($args);

        // Assign the handlers
        session_set_save_handler(
          array(&$this,"open"),    array(&$this,"close"),
          array(&$this,"read"),    array(&$this,"write"),
          array(&$this,"destroy"), array(&$this,"gc")
        );

        // Check for pollution
        if (ini_get('register_globals')) {
            // First thing we do is ensure that there is no attempted pollution
            // of the session namespace (yes, we still need this in this case)
            foreach($GLOBALS as $k=>$v) {
                if (substr($k,0,5) == self::PREFIX) {
                    throw new SessionException('xarSession init: Session Support initialisation failed.');
                }
            }
        }
    }

    /**
     * Destructor for the session handler
     *
     * @return void
     **/
    function __destruct()
    {
        // Make sure we write dirty data before we lose this object
        session_write_close();
    }

    /**
     * Set all PHP options for Xaraya session handling
     *
     * @param $args['securityLevel'] the current security level
     * @param $args['duration'] duration of the session
     * @param $args['inactivityTimeout']
     * @return boolean
     */
    private function setup(&$args)
    {
        //All in here is based on the possibility of changing
        //PHP's session related configuration
        if (!xarCore::funcIsDisabled('ini_set')) {
            // PHP configuration variables
            // Stop adding SID to URLs
            ini_set('session.use_trans_sid', 0);

            // How to store data
            ini_set('session.serialize_handler', 'php');

            // Use cookie to store the session ID
            ini_set('session.use_cookies', 1);

            // Name of our cookie
            if (empty($args['cookieName'])) $args['cookieName'] = self::COOKIE;
            ini_set('session.name', $args['cookieName']);

            if (empty($args['cookiePath'])) {
                $path = xarServer::getBaseURI();
                if (empty($path)) {
                    $path = '/';
                }
            } else {
                $path = $args['cookiePath'];
            }

            // Lifetime of our cookie
            switch ($args['securityLevel']) {
            case 'High':
                // Session lasts duration of browser
                $lifetime = 0;
                // Referer check defaults to the current host for security level High
                if (empty($args['refererCheck'])) {
                    $host = xarServer::getVar('HTTP_HOST');
                    $host = preg_replace('/:.*/', '', $host);
                    // this won't work for non-standard ports
                    //if (!xarCore::funcIsDisabled('ini_set')) ini_set('session.referer_check', "$host$path");
                    // this should be customized for multi-server setups wanting to
                    // share sessions
                    $args['refererCheck'] = $host;
                }
                break;
            case 'Low':
                // Session lasts unlimited number of days (well, lots, anyway)
                // (Currently set to 25 years)
                $lifetime = 788940000;
                break;
            case 'Medium':
            default:
                // Session lasts set number of days
                $lifetime = $args['duration'] * 86400;
                break;
            }
            ini_set('session.cookie_lifetime', $lifetime);

            // Referer check for the session cookie
            if (!empty($args['refererCheck'])) {
                ini_set('session.referer_check', $args['refererCheck']);
            }

            // Cookie path
            // this should be customized for multi-server setups wanting to share
            // sessions
            ini_set('session.cookie_path', $path);

            // Cookie domain
            // this is only necessary for sharing sessions across multiple servers,
            // and should be configurable for multi-site setups
            // Example: .Xaraya.com for all *.Xaraya.com servers
            // Example: www.Xaraya.com for www.Xaraya.com and *.www.Xaraya.com
            //$domain = xarServer::getVar('HTTP_HOST');
            //$domain = preg_replace('/:.*/', '', $domain);
            if (!empty($args['cookieDomain'])) {
                ini_set('session.cookie_domain', $args['cookieDomain']);
            }

            // Garbage collection
            ini_set('session.gc_probability', 1);

            // Inactivity timeout for user sessions
            ini_set('session.gc_maxlifetime', $args['inactivityTimeout'] * 60);

            // Auto-start session
            ini_set('session.auto_start', 1);
        }
        return true;
    }

    /**
     * Start the session
     *
     * This will call the handler, and it will tell us if
     * we need a new session or just continue the old one
     *
     */
    function start()
    {
        session_start();
    }

    /**
     * Set or get the session id
     *
     * @todo the static vs runtime method sucks, do we really need that?
     */
    function id($id= null)
    {
        $this->sessionId = $this->getId($id);
        return $this->sessionId;
    }

    static function getId($id = null)
    {
        if(isset($id))
            return session_id($id);
        else
            return session_id();
    }

    /**
     * Getter for new isNew
     *
     */
    function isNew()
    {
        return $this->isNew;
    }

    /**
     * Register a new session in our container
     *
     * @throws SQLException
     */
    function register($ipAddress)
    {
        try {
            $this->db->begin();
            $query = "INSERT INTO $this->tbl (id, ip_addr, role_id, first_use, last_use)
                      VALUES (?,?,?,?,?)";
            $bindvars = array($this->sessionId, $ipAddress, self::$anonId, time(), time());
            $stmt = $this->db->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
            $this->db->commit();
        } catch (SQLException $e) {
            // The rollback is useless, since there's only one statement (but the isolation level might be useful)
            // so leave transaction in. What should we do here, the registering of the session failed, we need
            // to handle that somehow a bit more friendly.
            $this->db->rollback();
            throw $e;
        }
        // Generate a random number, used for
        // some authentication
        srand((double) microtime(true) * 1000000.0);
        $this->setVar('rand', rand());

        $this->ipAddress = $ipAddress;
        return true;
    }

    /**
     * Continue an existing session
     *
     */
    function current()
    {  return true;
    }


    /**
     * PHP function to open the session
     * 
     */
    function open($path, $name)
    {   // Nothing to do - database opened elsewhere
        return true;
    }

    /**
     * PHP function to close the session
     * 
     */
    function close()
    {   // Nothing to do - database closed elsewhere
        return true;
    }

    /**
     * PHP function to read a set of session variables
     * 
     */
    function read($sessionId)
    {
        $query = "SELECT role_id, ip_addr, last_use, vars FROM $this->tbl WHERE id = ?";
        $stmt = $this->db->prepareStatement($query);
        $result = $stmt->executeQuery(array($sessionId),xarDB::FETCHMODE_NUM);

        if ($result->first()) {
            // Already have this session
            $this->isNew = false;
            list($XARSVid, $this->ipAddress, $lastused, $vars) = $result->getRow();
            // in case garbage collection didn't have the opportunity to do its job
            if (!empty(self::$securityLevel) &&
                self::$securityLevel == 'High') {
                $timeoutSetting = time() - (self::$inactivityTimeout * 60);
                if ($lastused < $timeoutSetting) {
                    // force a reset of the userid (but use the same sessionid)
                    $this->setUserInfo(self::$anonId, 0);
                    $this->ipAddress = '';
                    $vars = '';
                }
            }
            // Keep track of when this session was last saved
            self::saveTime($lastused);
        } else {
            $_SESSION[self::PREFIX.'role_id'] = self::$anonId;

            $this->ipAddress = '';
            $vars = '';
        }
        $result->close();

        // We *have to* make sure this returns a string!!
        return (string) $vars;
    }

    /**
     * PHP function to write a set of session variables
     *
     * @todo don't bother saving when nothing has been updated? See saveTime() below
     * @throws Exception
     */
    function write($sessionId, $vars)
    {
        try {
            $this->db->begin();
            // FIXME: We had to do qstr here, cos the query failed for some reason
            // This is apparently because this is in a session write handler.
            // Additional notes:
            // * apache 2 on debian linux segfaults
            // UPDATE: Could this be because the vars column is a BLOB (i.e. binary) ?
            $query = "UPDATE $this->tbl SET vars = ".
                $this->db->qstr($vars) . ", last_use = " .
                $this->db->qstr(time()). "WHERE id = ".
                $this->db->qstr($sessionId);
            $this->db->executeUpdate($query);
            $this->db->commit();
        } catch (Exception $e) {
            //$this->db->rollback(); (why was commented out again?)
            throw $e;
        }
        return true;
    }

    /**
     * PHP function to destroy a session
     *
     * 
     * @throws SQLException
     */
    function destroy($sessionId)
    {
        try {
            $this->db->begin();
            $query = "DELETE FROM $this->tbl WHERE id = ?";
            $this->db->execute($query,array($sessionId));
            $this->db->commit();
        } catch (SQLException $e) {
            $this->db->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * PHP function to garbage collect session information
     *
     * 
     * @throws SQLException
     */
    function gc($maxlifetime)
    {
        $timeoutSetting = time() - (self::$inactivityTimeout * 60);
        $bindvars = array();
        switch (self::$securityLevel) {
        case 'Low':
            // Low security - delete session info if user decided not to
            //                remember themself
            $where = "remember = ? AND  last_use < ?";
            $bindvars[] = false;
            $bindvars[] = $timeoutSetting;
            break;
        case 'Medium':
            // Medium security - delete session info if session cookie has
            //                   expired or user decided not to remember
            //                   themself
            $where = "(remember = ? AND last_use <  ?) OR first_use < ?";
            $bindvars[] = false;
            $bindvars[] = $timeoutSetting;
            $bindvars[] = (time()- (self::$duration * 86400));
            break;
        case 'High':
        default:
            // High security - delete session info if user is inactive
            $where = "last_use < ?";
            $bindvars[] = $timeoutSetting;
            break;
        }
        try {
            $this->db->begin();
            $query = "DELETE FROM $this->tbl WHERE $where";
            $stmt = $this->db->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
            $this->db->commit();
        } catch (SQLException $e) {
            $this->db->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * Initialise the Session Support
     *
     * @return boolean true
     */
    public static function init(array $args = array())
    {
        if (empty($args)) {
            $args = self::getConfig();
        }
        /* @todo: get rid of the global */
        //$GLOBALS['xarSession_systemArgs'] = $args;
        self::$securityLevel = $args['securityLevel'];
        self::$duration = $args['duration'];
        self::$inactivityTimeout = $args['inactivityTimeout'];
        //self::$cookieName = $args['cookieName'];
        //self::$cookiePath = $args['cookiePath'];
        //self::$cookieDomain = $args['cookieDomain'];
        //self::$refererCheck = $args['refererCheck'));

	self::$anonId = (int) xarConfigVars::get(null, 'Site.User.AnonymousUID', 5);
        if (!defined('_XAR_ID_UNREGISTERED')) {
            define('_XAR_ID_UNREGISTERED', self::$anonId);
        }

        // Register the SessionCreate event
        // this is now registered during modules module init
        // xarEvents::register('SessionCreate');

        // Register tables this subsystem uses
        $tables = array('session_info' => xarDB::getPrefix() . '_session_info');
        xarDB::importTables($tables);

        // Set up the session object
        $session = new xarSession($args);

        // Start the session, this will call xarSession:read, and
        // it will tell us if we need to start a new session or just
        // to continue the current session
        $session->start();
        $sessionId = $session->id();

        // Get  client IP addr, so we can register or continue a session
        $forwarded = xarServer::getVar('HTTP_X_FORWARDED_FOR');
        if (!empty($forwarded)) {
            $ipAddress = preg_replace('/,.*/', '', $forwarded);
        } else {
            $ipAddress = xarServer::getVar('REMOTE_ADDR') || '-';
        }

        // If it's new, register it, otherwise use the existing.
        if ($session->isNew()) {
            if($session->register($ipAddress)) {
                // Congratulations. We have created a new session
                //xarEvents::trigger('SessionCreate');
                xarEvents::notify('SessionCreate');
            } else {
                // Registering failed, now what?
            }
        } else {
            // Not all ISPs have a fixed IP or a reliable X_FORWARDED_FOR
            // so we don't test for the IP-address session var
            $session->current();
        }
        return true;
    }

    static function getConfig()
    {
        $systemArgs = array(
            'securityLevel'     => xarConfigVars::get(null, 'Site.Session.SecurityLevel'),
            'duration'          => xarConfigVars::get(null, 'Site.Session.Duration'),
            'inactivityTimeout' => xarConfigVars::get(null, 'Site.Session.InactivityTimeout'),
            'cookieName'        => xarConfigVars::get(null, 'Site.Session.CookieName'),
            'cookiePath'        => xarConfigVars::get(null, 'Site.Session.CookiePath'),
            'cookieDomain'      => xarConfigVars::get(null, 'Site.Session.CookieDomain'),
            'refererCheck'      => xarConfigVars::get(null, 'Site.Session.RefererCheck'));
        return $systemArgs;
    }

    /**
     * Get a session variable
     *
     * @param string $name name of the session variable to get
     */
    static function getVar($name)
    {
        $var = self::PREFIX . $name;

        if (isset($_SESSION[$var])) {
            return $_SESSION[$var];
        } elseif ($name == 'role_id') {
            // mrb: why is this again?
            $_SESSION[$var] = self::$anonId;
            return $_SESSION[$var];
        }
    }

    /**
     * Set a session variable
     * @param string $name name of the session variable to set
     * @param mixed $value value to set the named session variable
     */
    static function setVar($name, $value)
    {
        assert(!is_null($value));
        // security checks : do not allow to set the id or mess with the session serialization
        if ($name == 'role_id' || strpos($name,'|') !== false) return false;

        $var = self::PREFIX . $name;
        $_SESSION[$var] = $value;
        return true;
    }

    /**
     * Delete a session variable
     * @param string $name name of the session variable to delete
     */
    static function delVar($name)
    {
        if ($name == 'role_id') return false;

        $var = self::PREFIX . $name;

        if (!isset($_SESSION[$var])) {
            return false;
        }
        unset($_SESSION[$var]);
        // no longer needed here
        //if (ini_get('register_globals')) {
        //    session_unregister($var);
        //}
        return true;
    }

    /**
     * Set user info
     *
     * @throws SQLException
     * @todo this seems a strange duck (only used in roles by the looks of it)
     */
    static function setUserInfo($userId, $rememberSession)
    {
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();

        $sessioninfoTable = $xartable['session_info'];
        try {
            $dbconn->begin();
            $query = "UPDATE $sessioninfoTable
                      SET role_id = ? ,remember = ?
                      WHERE id = ?";
            $bindvars = array($userId, $rememberSession, self::getId());
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }

        $_SESSION[self::PREFIX.'role_id'] = $userId;
        return true;
    }

    /**
     * When was this session last saved ?
     */
    static function saveTime($lastused = 0)
    {
        // initialize saveTime if necessary
        if (!isset(self::$lastSaved) || !empty($lastused)) {
            self::$lastSaved = (int) $lastused;
        }
        return self::$lastSaved;
    }

    /**
     * Get the anonymous userId
     * @return ?int
     */
    public static function getAnonId()
    {
        return self::$anonId;
    }

    /**
     * Get the configured security level
     *
     * @todo Is this used anywhere outside the session class itself?
     */
    public static function getSecurityLevel()
    {
        return self::$securityLevel;
    }

    /**
     * Clear all the sessions in the sessions table
     *
     * @param array<mixed> $spared a list of roles IDs whose sessions are left untouched
     */
    public static function clear($spared=[])
    {
        if (!is_array($spared)) {
			$msg = xarML('Not an array: \'$spared\'');
			throw new BadParameterException($msg);
        }
        	
        $no_spared = empty($spared);
        try {
            self::$instance->db->begin();
            $tbl = self::$instance->tbl;
			if ($no_spared) {
				$query = "DELETE FROM $tbl";
	            self::$instance->db->execute($query);
			} else {
				$query = "DELETE FROM $tbl WHERE role_id NOT IN (";
				$spared_fill = array_fill(0, count($spared), '?');
				$spared_fill = implode(',', $spared_fill);
				$query .= $spared_fill;
				$query .= ")";
	            self::$instance->db->execute($query,$spared);
			}
            self::$instance->db->commit();
        } catch (SQLException $e) {
            self::$instance->db->rollback();
            throw $e;
        }
        return true;
    }
}

// Legacy calls - import by default for now...
//sys::import('xaraya.legacy.sessions');
