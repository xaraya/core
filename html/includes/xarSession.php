<?php
/**
 * Session Support
 *
 * @package sessions
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
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
 * Initialise the Session Support
 * 
 * @author Jim McDonald, Marco Canini <marco@xaraya.com>
 * @return bool true
 */
function xarSession_init($args, $whatElseIsGoingLoaded)
{
    /* @todo: get rid of the global */
    $GLOBALS['xarSession_systemArgs'] = $args;

    // Register the SessionCreate event
    xarEvt_registerEvent('SessionCreate');

    // Register tables this subsystem uses
    $systemPrefix = xarDBGetSystemTablePrefix();
    $tables = array('session_info' => $systemPrefix . '_session_info');
    xarDB::importTables($tables); 

    // Set up the session object
    $session = new xarSession($args);
  
    // Start the session, this will call xarSession:read, and
    // it will tell us if we need to start a new session or just
    // to continue the current session
    $session->start();
    $sessionId = $session->id();

    // Get  client IP addr, so we can register or continue a session
    $forwarded = xarServerGetVar('HTTP_X_FORWARDED_FOR');
    if (!empty($forwarded)) {
        $ipAddress = preg_replace('/,.*/', '', $forwarded);
    } else {
        $ipAddress = xarServerGetVar('REMOTE_ADDR');
    }

    // If it's new, register it, otherwise use the existing.
    if ($session->isNew()) {
        $session->register($ipAddress);
    } else {
        // Not all ISPs have a fixed IP or a reliable X_FORWARDED_FOR
        // so we don't test for the IP-address session var
        $session->current();
    }
    
    // Subsystem initialized, register a handler to run when the request is over
    register_shutdown_function ('xarSession__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for the session subsystem
 *
 * This function is the shutdown handler for the 
 * sessions subsystem. It runs on the end of a request
 *
 */
function xarSession__shutdown_handler()
{
    // Close the session we started on init
    // as this is a shutdown handler, we know it will only
    // run if the subsystem was initialized as well
    session_write_close(); // This writes 'dirty' session data at the end of the request
}

/**
 * Get the configured security level
 *
 * @todo Is this used anywhere outside the session class itself?
 */
function xarSessionGetSecurityLevel()
{
    return $GLOBALS['xarSession_systemArgs']['securityLevel'];
}

/*
 * Session variables here are a bit 'different'.  Because they sit in the
 * global namespace we use a couple of helper functions to give them their
 * own prefix, and also to force users to set new values for them if they
 * require.  This avoids blatant or accidental over-writing of session
 * variables.
 *

/**
 * Get a session variable
 *
 * @param name name of the session variable to get
 */
function xarSessionGetVar($name) { return xarSession::getVar($name); }

/**
 * Set a session variable
 * @param name name of the session variable to set
 * @param value value to set the named session variable
 */
function xarSessionSetVar($name, $value){ return xarSession::setVar($name, $value); }

/**
 * Delete a session variable
 * @param name name of the session variable to delete
 */
function xarSessionDelVar($name){ return xarSession::delVar($name); }

function xarSessionGetId(){ return xarSession::getId(); }

// PROTECTED FUNCTIONS
/** mrb: if it's protected, how come roles uses it? */
function xarSession_setUserInfo($userId, $rememberSession)
{ return xarSession::setUserInfo($userId, $rememberSession); }

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

class xarSession implements IsessionHandler
{
    const  PREFIX='XARSV';    // Reserved by us for our session vars
    private $db;               // We store sessioninfo in the database
    private $tbl;              // Container for the session info
    private $isNew = true;     // Flag signalling if we're dealing with a new session

    private $sessionId = null; // The id assigned to us.
    private $ipAddress = '';   // IP-address belonging to this session.

    function __construct(&$args)
    {
        // Set up our container.
        $this->db =& xarDBGetConn();
        $tbls =& xarDBGetTables();
        $this->tbl = $tbls['session_info'];

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
                    throw new SessionException('xarSession_init: Session Support initialisation failed.');
                }
            }
        }
    }

    /**
     * Set all PHP options for Xaraya session handling
     *
     * @param $args['securityLevel'] the current security level
     * @param $args['duration'] duration of the session
     * @param $args['inactivityTimeout']
     * @return bool
     */
    private function setup(&$args)
    {
        //All in here is based on the possibility of changing
        //PHP's session related configuration
        if (!xarFuncIsDisabled('ini_set')) {
            // PHP configuration variables
            // Stop adding SID to URLs
            ini_set('session.use_trans_sid', 0);
            
            // User-defined save handler
            ini_set('session.save_handler', 'user');
            
            // How to store data
            ini_set('session.serialize_handler', 'php');
            
            // Use cookie to store the session ID
            ini_set('session.use_cookies', 1);
            
            // Name of our cookie
            if (empty($args['cookieName'])) {
                $args['cookieName'] = 'XARAYASID';
            }
            ini_set('session.name', $args['cookieName']);
            
            if (empty($args['cookiePath'])) {
                $path = xarServerGetBaseURI();
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
                    $host = xarServerGetVar('HTTP_HOST');
                    $host = preg_replace('/:.*/', '', $host);
                    // this won't work for non-standard ports
                    //if (!xarFuncIsDisabled('ini_set')) ini_set('session.referer_check', "$host$path");
                    // this should be customized for multi-server setups wanting to
                    // share sessions
                    $args['refererCheck'] = $host;
                }
                break;
            case 'Medium':
                // Session lasts set number of days
                $lifetime = $args['duration'] * 86400;
                break;
            case 'Low':
                // Session lasts unlimited number of days (well, lots, anyway)
                // (Currently set to 25 years)
                $lifetime = 788940000;
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
            //$domain = xarServerGetVar('HTTP_HOST');
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
        $this->sessionId = self::getId($id);
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
     * Register a new session in our containser
     *
     */
    function register($ipAddress)
    {
        try {
            $this->db->begin();
            $query = "INSERT INTO $this->tbl
                  (xar_sessid, xar_ipaddr, xar_uid, xar_firstused, xar_lastused)
                  VALUES (?,?,?,?,?)";
            $bindvars = array($this->sessionId, $ipAddress, _XAR_ID_UNREGISTERED, time(), time());
            $this->db->Execute($query,$bindvars);
            $this->db->commit();
        } catch (SQLException $e) {
            $this->db->rollback();
            throw $e;
        }
        // Generate a random number, used for
        // some authentication
        srand((double) microtime() * 1000000);
        xarSessionSetVar('rand', rand());
        
        $this->ipAddress = $ipAddress;
        // Congratulations. We have created a new session
        xarEvt_trigger('SessionCreate');
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
     * @private
     */
    function open($path, $name)
    {   // Nothing to do - database opened elsewhere
        return true;
    }

    /**
     * PHP function to close the session
     * @private
     */
    function close()
    {   // Nothing to do - database closed elsewhere
        return true;
    }

    /**
     * PHP function to read a set of session variables
     * @private
     */
    function read($sessionId)
    {
        // FIXME: in session2 the uid is not used anymore, can we safely migrate this 
        //        out? At least the roles/privileges modules are using it actively
        $query = "SELECT xar_uid, xar_ipaddr, xar_lastused, xar_vars
              FROM $this->tbl WHERE xar_sessid = ?";
        $result =& $this->db->Execute($query,array($sessionId),ResultSet::FETCHMODE_NUM);
        
        if (!$result->EOF) {
            // Already have this session
            $this->isNew = false;
            list($XARSVuid, $this->ipAddress, $lastused, $vars) = $result->getRow();
            // in case garbage collection didn't have the opportunity to do its job
            if (!empty($GLOBALS['xarSession_systemArgs']['securityLevel']) &&
                $GLOBALS['xarSession_systemArgs']['securityLevel'] == 'High') {
                $timeoutSetting = time() - ($GLOBALS['xarSession_systemArgs']['inactivityTimeout'] * 60);
                if ($lastused < $timeoutSetting) {
                    // force a reset of the userid (but use the same sessionid)
                    xarSession_setUserInfo(_XAR_ID_UNREGISTERED, 0);
                    $this->ipAddress = '';
                    $vars = '';
                }
            }
        } else {
            $_SESSION[self::PREFIX.'uid'] = _XAR_ID_UNREGISTERED;
            
            $this->ipAddress = '';
            $vars = '';
        }
        $result->Close();

        // We *have to* make sure this returns a string!! 
        return (string) $vars;
    }

    /**
     * PHP function to write a set of session variables
     * @private
     */
    function write($sessionId, $vars)
    {
        try {
            $this->db->begin();
            // FIXME: We had to do qstr here, cos the query failed for some reason
            // This is apparently because this is in a session write handler.
            // Additional notes:
            // * apache 2 on debian linux segfaults
            // UPDATE: Could this be because the xar_vars column is a BLOB (i.e. binary) ?
            $query = "UPDATE $this->tbl SET xar_vars = ". 
                $this->db->qstr($vars) . ", xar_lastused = " . 
                $this->db->qstr(time()). "WHERE xar_sessid = ".
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
     * @private
     */
    function destroy($sessionId)
    {
        try {
            $this->db->begin();
            $query = "DELETE FROM $this->tbl WHERE xar_sessid = ?";
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
     * @private
     */
    function gc($maxlifetime)
    {
        $timeoutSetting = time() - ($GLOBALS['xarSession_systemArgs']['inactivityTimeout'] * 60);
        $bindvars = array();
        switch ($GLOBALS['xarSession_systemArgs']['securityLevel']) {
        case 'Low':
            // Low security - delete session info if user decided not to
            //                remember themself
            $where = "xar_remembersess = ? AND  xar_lastused < ?";
            $bindvars[] = 0;
            $bindvars[] = $timeoutSetting;
            break;
        case 'Medium':
            // Medium security - delete session info if session cookie has
            //                   expired or user decided not to remember
            //                   themself
            $where = "(xar_remembersess = ? AND xar_lastused <  ?) OR
                   xar_firstused < ?";
            $bindvars[] = 0;
            $bindvars[] = $timeoutSetting;
            $bindvars[] = (time()- ($GLOBALS['xarSession_systemArgs']['duration'] * 86400));
            break;
        case 'High':
        default:
            // High security - delete session info if user is inactive
            $where = "xar_lastused < ?";
            $bindvars[] = $timeoutSetting;
            break;
        }
        try {
            $this->db->begin();
            $query = "DELETE FROM $this->tbl WHERE $where";
            $this->db->Execute($query,$bindvars);
            $this->db->commit();
        } catch (SQLException $e) {
            $this->db->rollback();
            throw $e;
        }
        return true;
    }

    static function getVar($name)
    {
        $var = self::PREFIX . $name;

        if (isset($_SESSION[$var])) {
            return $_SESSION[$var];
        } elseif ($name == 'uid') {
            // mrb: why is this again?
            $_SESSION[$var] = _XAR_ID_UNREGISTERED;
            return $_SESSION[$var];
        }
    }

    static function setVar($name, $value)
    {
        assert('!is_null($value); /* Not allowed to set variable to NULL value */');
        // security checks : do not allow to set the uid or mess with the session serialization
        if ($name == 'uid' || strpos($name,'|') !== FALSE) return false;

        $var = self::PREFIX . $name;

        // also needed for PHP 4.1.2 - cfr. bug 3679
        if (isset($_SESSION)) {
            $_SESSION[$var] = $value;
        }
        return true;
    }
    
    static function delVar($name)
    {
        if ($name == 'uid') return false;
        
        $var = self::PREFIX . $name;

        if (!isset($_SESSION[$var])) {
            return false;
        }
        unset($_SESSION[$var]);
        // still needed here too
        // mrb: why?
        if (ini_get('register_globals')) {
            session_unregister($var);
        }
        return true;
    }

    /**
     * Set user info
     *
     * @todo this seems a strang duck (only used in roles by the looks of it)
     */
    static function setUserInfo($userId, $rememberSession)
    {
        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $sessioninfoTable = $xartable['session_info'];
        try {
            $dbconn->begin();
            $query = "UPDATE $sessioninfoTable
                      SET xar_uid = ? ,xar_remembersess = ?
                      WHERE xar_sessid = ?";
            $bindvars = array($userId, $rememberSession, self::getId());
            $dbconn->Execute($query,$bindvars);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }

        $_SESSION[self::PREFIX.'uid'] = $userId;
    return true;
    }

}
?>
