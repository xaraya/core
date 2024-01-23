<?php
/**
 * @package core\sessions
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Sessions;

use xarCore;
use xarDB;
use xarEvents;
use xarObject;
use xarServer;
use xarSession;
use Connection;
use SessionHandlerInterface;
use Exception;
use BadParameterException;
use SQLException;
use sys;

sys::import('xaraya.sessions.interface');
sys::import('xaraya.sessions.exception');

/**
 * Class to model the default session handler
 *
 *
 * @todo this is a temp, since the obvious plan is to have a factory here
 */
interface iSessionHandler extends SessionHandlerInterface
{
    public function register(string $ipAddress): bool;
    public function start(): void;
    public function id(?string $id = null): string|bool;
    public function isNew(): bool;
    public function current(): bool;
    /**
    public function open($path, $name);
    public function close();
    public function read($sessionId);
    public function write($sessionId, $vars);
    public function destroy($sessionId);
    public function gc($maxlifetime);
     */
}

/**
 * Session Support
 *
 * @package core\sessions
 */
class SessionHandler extends xarObject implements iSessionHandler, SessionInterface
{
    public const  PREFIX = 'XARSV';     // Reserved by us for our session vars
    public const  COOKIE = 'XARAYASID'; // Our cookiename
// TODO: the following line presently causes PDO to break
//    private ?Connection $db;               // We store sessioninfo in the database
    private $db;                        // We store sessioninfo in the database
    private string $tbl;                // Container for the session info
    private bool $isNew = true;         // Flag signalling if we're dealing with a new session

    private ?string $sessionId = null;  // The id assigned to us.
    private string $ipAddress = '';     // IP-address belonging to this session.

    /**
     * Constructor for the session handler
     *
     * @param array<string, mixed> $args not by reference anymore
     * @uses xarSession::setInstance()
     * @return void
     * @throws SessionException
     **/
    public function __construct($args)
    {
        // Register tables this subsystem uses
        $tables = ['session_info' => xarDB::getPrefix() . '_session_info'];
        xarDB::importTables($tables);

        // Set up our container.
        $this->db = xarDB::getConn();
        $tbls     = xarDB::getTables();
        $this->tbl = $tbls['session_info'];

        // Put a reference to this instance into a static property
        xarSession::setInstance($this);

        // Set up the environment
        $this->setup($args);

        // Assign the handlers
        session_set_save_handler(
            [&$this,"open"],
            [&$this,"close"],
            [&$this,"read"],
            [&$this,"write"],
            [&$this,"destroy"],
            [&$this,"gc"]
        );

        // Check for pollution
        if (ini_get('register_globals')) {
            // First thing we do is ensure that there is no attempted pollution
            // of the session namespace (yes, we still need this in this case)
            foreach($GLOBALS as $k => $v) {
                if (substr($k, 0, 5) == self::PREFIX) {
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
    public function __destruct()
    {
        // Make sure we write dirty data before we lose this object
        session_write_close();
    }

    /**
     * Set all PHP options for Xaraya session handling
     *
     * @param array<string, mixed> $args not by reference anymore
     * with:
     *     $args['securityLevel'] the current security level
     *     $args['duration'] duration of the session
     *     $args['inactivityTimeout']
     * @return boolean
     */
    private function setup($args)
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
            if (empty($args['cookieName'])) {
                $args['cookieName'] = self::COOKIE;
            }
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
     * Initialize the session after setup
     * @return bool
     */
    public function initialize()
    {
        // Start the session, this will call xarSession:read, and
        // it will tell us if we need to start a new session or just
        // to continue the current session
        $this->start();
        $sessionId = $this->id();

        // Get  client IP addr, so we can register or continue a session
        $forwarded = xarServer::getVar('HTTP_X_FORWARDED_FOR');
        if (!empty($forwarded)) {
            $ipAddress = preg_replace('/,.*/', '', $forwarded);
        } else {
            $ipAddress = xarServer::getVar('REMOTE_ADDR') ?? '-';
        }

        // If it's new, register it, otherwise use the existing.
        if ($this->isNew()) {
            if($this->register($ipAddress)) {
                // Congratulations. We have created a new session
                //xarEvents::trigger('SessionCreate');
                xarEvents::notify('SessionCreate');
            } else {
                // Registering failed, now what?
            }
        } else {
            // Not all ISPs have a fixed IP or a reliable X_FORWARDED_FOR
            // so we don't test for the IP-address session var
            $this->current();
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
    public function start(): void
    {
        session_start();
    }

    /**
     * Set or get the session id
     *
     * @todo the static vs runtime method sucks, do we really need that?
     */
    public function id(?string $id = null): string|bool
    {
        $this->sessionId = (string) $this->getId($id);
        return $this->sessionId;
    }

    /**
     * Get (or set) the session id
     */
    public function getId($id = null): string|bool
    {
        if(isset($id)) {
            return session_id($id);
        } else {
            return session_id();
        }
    }

    /**
     * Getter for new isNew
     *
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * Register a new session in our container
     *
     * @throws SQLException
     */
    public function register(string $ipAddress): bool
    {
        try {
            $this->db->begin();
            $query = "INSERT INTO $this->tbl (id, ip_addr, role_id, first_use, last_use)
                      VALUES (?,?,?,?,?)";
            $bindvars = [$this->sessionId, $ipAddress, xarSession::$anonId, time(), time()];
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
        srand((int) (microtime(true) * 1000000.0));
        $this->setVar('rand', rand());

        $this->ipAddress = $ipAddress;
        return true;
    }

    /**
     * Continue an existing session
     *
     */
    public function current(): bool
    {
        return true;
    }


    /**
     * PHP function to open the session
     *
     */
    public function open($path, $name): bool
    {   // Nothing to do - database opened elsewhere
        return true;
    }

    /**
     * PHP function to close the session
     *
     */
    public function close(): bool
    {   // Nothing to do - database closed elsewhere
        return true;
    }

    /**
     * PHP function to read a set of session variables
     *
     */
    public function read($sessionId): string|false
    {
        $query = "SELECT role_id, ip_addr, last_use, vars FROM $this->tbl WHERE id = ?";
        $stmt = $this->db->prepareStatement($query);
        $result = $stmt->executeQuery([$sessionId], xarDB::FETCHMODE_NUM);

        if ($result->first()) {
            // Already have this session
            $this->isNew = false;
            [$XARSVid, $this->ipAddress, $lastused, $vars] = $result->getRow();
            // in case garbage collection didn't have the opportunity to do its job
            if (!empty(xarSession::getSecurityLevel()) &&
                xarSession::getSecurityLevel() == 'High') {
                $timeoutSetting = xarSession::getTimeoutSetting();
                if ($lastused < $timeoutSetting) {
                    // force a reset of the userid (but use the same sessionid)
                    $this->setUserInfo(xarSession::$anonId, 0);
                    $this->ipAddress = '';
                    $vars = '';
                }
            }
            // Keep track of when this session was last saved
            xarSession::saveTime($lastused);
        } else {
            $_SESSION[self::PREFIX . 'role_id'] = xarSession::$anonId;

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
    public function write($sessionId, $vars): bool
    {
        try {
            $this->db->begin();
            // FIXME: We had to do qstr here, cos the query failed for some reason
            // This is apparently because this is in a session write handler.
            // Additional notes:
            // * apache 2 on debian linux segfaults
            // UPDATE: Could this be because the vars column is a BLOB (i.e. binary) ?
            $query = "UPDATE $this->tbl SET vars = " .
                $this->db->qstr($vars) . ", last_use = " .
                $this->db->qstr(time()) . "WHERE id = " .
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
    public function destroy($sessionId): bool
    {
        try {
            $this->db->begin();
            $query = "DELETE FROM $this->tbl WHERE id = ?";
            $this->db->execute($query, [$sessionId]);
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
    public function gc($maxlifetime): int|false
    {
        $timeoutSetting = xarSession::getTimeoutSetting();
        $bindvars = [];
        switch (xarSession::getSecurityLevel()) {
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
                $bindvars[] = (time() - (xarSession::getDuration() * 86400));
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
        return 1;
    }

    /**
     * Get a session variable
     * @param string $name name of the session variable to get
     */
    public function getVar($name)
    {
        $var = self::PREFIX . $name;

        if (isset($_SESSION[$var])) {
            return $_SESSION[$var];
        } elseif ($name == 'role_id') {
            // mrb: why is this again?
            $_SESSION[$var] = xarSession::$anonId;
            return $_SESSION[$var];
        }
    }

    /**
     * Set a session variable
     * @param string $name name of the session variable to set
     * @param mixed $value value to set the named session variable
     */
    public function setVar($name, $value)
    {
        assert(!is_null($value));
        // security checks : do not allow to set the id or mess with the session serialization
        if ($name == 'role_id' || strpos($name, '|') !== false) {
            return false;
        }

        $var = self::PREFIX . $name;
        $_SESSION[$var] = $value;
        return true;
    }

    /**
     * Delete a session variable
     * @param string $name name of the session variable to delete
     */
    public function delVar($name)
    {
        if ($name == 'role_id') {
            return false;
        }

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
     * @throws SQLException
     * @todo this seems a strange duck (only used in roles by the looks of it)
     */
    public function setUserInfo($userId, $rememberSession)
    {
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();

        $sessioninfoTable = $xartable['session_info'];
        try {
            $dbconn->begin();
            $query = "UPDATE $sessioninfoTable
                      SET role_id = ? ,remember = ?
                      WHERE id = ?";
            $bindvars = [$userId, $rememberSession, $this->getId()];
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }

        $_SESSION[self::PREFIX . 'role_id'] = $userId;
        return true;
    }

    /**
     * Clear all the sessions in the sessions table
     *
     * @param array<mixed> $spared a list of roles IDs whose sessions are left untouched
     */
    public function clear($spared = [])
    {
        if (!is_array($spared)) {
            $msg = xarML('Not an array: \'$spared\'');
            throw new BadParameterException($msg);
        }

        $no_spared = empty($spared);
        try {
            $this->db->begin();
            $tbl = $this->tbl;
            if ($no_spared) {
                $query = "DELETE FROM $tbl";
                $this->db->execute($query);
            } else {
                $query = "DELETE FROM $tbl WHERE role_id NOT IN (";
                $spared_fill = array_fill(0, count($spared), '?');
                $spared_fill = implode(',', $spared_fill);
                $query .= $spared_fill;
                $query .= ")";
                $this->db->execute($query, $spared);
            }
            $this->db->commit();
        } catch (SQLException $e) {
            $this->db->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * @param mixed $context
     * @return void
     */
    public function setContext($context)
    {
        // not used in default session handler
    }
}
