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
 */

/**
 * Session exception class
 *
 */
class SessionException extends Exception
{
}

/**
 * Initialise the Session Support
 * 
 * @author Jim McDonald, Marco Canini <marco@xaraya.com>
 * @return bool true
 */
function xarSession_init($args, $whatElseIsGoingLoaded)
{
    $GLOBALS['xarSession_systemArgs'] = $args;

    // Session Support Tables
    $systemPrefix = xarDBGetSystemTablePrefix();
    $tables = array('session_info' => $systemPrefix . '_session_info');
    xarDB_importTables($tables);

    // Register the SessionCreate event
    xarEvt_registerEvent('SessionCreate');

    xarSession__setup($args);

    if (ini_get('register_globals')) {
        // First thing we do is ensure that there is no attempted pollution
        // of the session namespace (yes, we still need this in this case)
        foreach($GLOBALS as $k=>$v) {
            if (substr($k,0,5) == 'XARSV') {
                throw new SessionException('xarSession_init: Session Support initialisation failed.');
            }
        }
    }
    // Start the session, this will call xarSession__phpRead, and
    // it will tell us if we need to start a new session or just
    // to continue the current session
    session_start();

    $sessionId = session_id();

    // TODO : add an admin option to re-activate this e.g. for
    //        Security Level "High" ?

    // Get  client IP addr
    $forwarded = xarServerGetVar('HTTP_X_FORWARDED_FOR');
    if (!empty($forwarded)) {
        $ipAddress = preg_replace('/,.*/', '', $forwarded);
    } else {
        $ipAddress = xarServerGetVar('REMOTE_ADDR');
    }

    if ($GLOBALS['xarSession_isNewSession']) {
        xarSession__new($sessionId, $ipAddress);
    } else {
        // Not all ISPs have a fixed IP or a reliable X_FORWARDED_FOR
        // so we don't test for the IP-address session var
        xarSession__current($sessionId);
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
    xarSession_Close();
}

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
function xarSessionGetVar($name)
{
    $var = 'XARSV' . $name;

    // First try to handle stuff through _SESSION
    if (isset($_SESSION[$var])) {
        return $_SESSION[$var];
    } elseif ($name == 'uid') {
        $_SESSION[$var] = _XAR_ID_UNREGISTERED;
        return $_SESSION[$var];
    }
}

/**
 * Set a session variable
 * @param name name of the session variable to set
 * @param value value to set the named session variable
 */
function xarSessionSetVar($name, $value)
{
    assert('!is_null($value); /* Not allowed to set variable to NULL value */');
    // security checks : do not allow to set the uid or mess with the session serialization
    if ($name == 'uid' || strpos($name,'|') !== FALSE) return false;

    $var = 'XARSV' . $name;

    // also needed for PHP 4.1.2 - cfr. bug 3679
    if (isset($_SESSION)) {
        $_SESSION[$var] = $value;
    }
    return true;
}

/**
 * Delete a session variable
 * @param name name of the session variable to delete
 */
function xarSessionDelVar($name)
{
    if ($name == 'uid') return false;

    $var = 'XARSV' . $name;

    if (!isset($_SESSION[$var])) {
        return false;
    }
    unset($_SESSION[$var]);
    // still needed here too
    if (ini_get('register_globals')) {
        session_unregister($var);
    }
    return true;
}

function xarSessionGetId()
{
    return session_id();
}

// PROTECTED FUNCTIONS

function xarSession_setUserInfo($userId, $rememberSession)
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];
    try {
        $dbconn->begin();
        $query = "UPDATE $sessioninfoTable
                  SET xar_uid = ? ,xar_remembersess = ?
                  WHERE xar_sessid = ?";
        $bindvars = array($userId, $rememberSession, session_id());
        $dbconn->Execute($query,$bindvars);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    $_SESSION['XARSVuid'] = $userId;
    return true;
}

function xarSession_close()
{
    session_write_close();
}

// PRIVATE FUNCTIONS

/**
 * Set all PHP options for Xaraya session handling
 *
 * @param $args['securityLevel'] the current security level
 * @param $args['duration'] duration of the session
 * @param $args['inactivityTimeout']
 * @return bool
 */
function xarSession__setup($args)
{
    //All in here is based on the possibility of changing
    //PHP's session related configuration
    if (!xarFuncIsDisabled('ini_set'))
    {
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

    // Session handlers
    session_set_save_handler("xarSession__phpOpen",
                             "xarSession__phpClose",
                             "xarSession__phpRead",
                             "xarSession__phpWrite",
                             "xarSession__phpDestroy",
                             "xarSession__phpGC");
    return true;
}

/**
 * Continue a current session
 * @private
 * @param sessionId the session ID
 */
function xarSession__current($sessionId)
{
    // lastused field will be updated when writing the session variables
    return true;
}

/**
 * Create a new session
 * @private
 * @param sessionId the session ID
 * @param ipAddress the IP address of the host with this session
 */
function xarSession__new($sessionId, $ipAddress)
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    try {
        $dbconn->begin();
        $query = "INSERT INTO $sessioninfoTable
                  (xar_sessid, xar_ipaddr, xar_uid, xar_firstused, xar_lastused)
                  VALUES (?,?,?,?,?)";
        $bindvars = array($sessionId, $ipAddress, _XAR_ID_UNREGISTERED, time(), time());
        $dbconn->Execute($query,$bindvars);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Generate a random number, used for
    // some authentication
    srand((double) microtime() * 1000000);
    xarSessionSetVar('rand', rand());

    // Congratulations. We have created a new session
    xarEvt_trigger('SessionCreate');

    return true;
}

/**
 * PHP function to open the session
 * @private
 */
function xarSession__phpOpen($path, $name)
{
    // Nothing to do - database opened elsewhere
    return true;
}

/**
 * PHP function to close the session
 * @private
 */
function xarSession__phpClose()
{
    // Nothing to do - database closed elsewhere
    return true;
}

/**
 * PHP function to read a set of session variables
 * @private
 */
function xarSession__phpRead($sessionId)
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    // FIXME: in session2 the uid is not used anymore, can we safely migrate this 
    //        out? At least the roles/privileges modules are using it actively
    $query = "SELECT xar_uid, xar_ipaddr, xar_lastused, xar_vars
              FROM $sessioninfoTable WHERE xar_sessid = ?";

    $result =& $dbconn->Execute($query,array($sessionId),ResultSet::FETCHMODE_NUM);
    if (!$result) return;

    if (!$result->EOF) {
        $GLOBALS['xarSession_isNewSession'] = false;
        list($XARSVuid, $GLOBALS['xarSession_ipAddress'], $lastused, $vars) = $result->getRow();
        // in case garbage collection didn't have the opportunity to do its job
        if (!empty($GLOBALS['xarSession_systemArgs']['securityLevel']) &&
            $GLOBALS['xarSession_systemArgs']['securityLevel'] == 'High') {
            $timeoutSetting = time() - ($GLOBALS['xarSession_systemArgs']['inactivityTimeout'] * 60);
            if ($lastused < $timeoutSetting) {
                // force a reset of the userid (but use the same sessionid)
                xarSession_setUserInfo(_XAR_ID_UNREGISTERED, 0);
                $GLOBALS['xarSession_ipAddress'] = '';
                $vars = '';
            }
        }
    } else {
        $GLOBALS['xarSession_isNewSession'] = true;
        $_SESSION['XARSVuid'] = _XAR_ID_UNREGISTERED;
        
        $GLOBALS['xarSession_ipAddress'] = '';
        $vars = '';
    }
    $result->Close();

    return $vars;
}

/**
 * PHP function to write a set of session variables
 * @private
 */
function xarSession__phpWrite($sessionId, $vars)
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];
    try {
        $dbconn->begin();
        // FIXME: We had to do qstr here, cos the query failed for some reason
        // This is apparently because this is in a session write handler.
        // Additional notes:
        // * apache 2 on debian linux segfaults
        $query = "UPDATE $sessioninfoTable SET xar_vars = ". $dbconn->qstr($vars) . ", xar_lastused = " . $dbconn->qstr(time()). "WHERE xar_sessid = ".$dbconn->qstr($sessionId);
        $dbconn->executeUpdate($query);
        $dbconn->commit();
    } catch (Exception $e) {
        //$dbconn->rollback();
        throw $e;
    }
    return true;
}

/**
 * PHP function to destroy a session
 * @private
 */
function xarSession__phpDestroy($sessionId)
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    try {
        $dbconn->begin();
        $query = "DELETE FROM $sessioninfoTable WHERE xar_sessid = ?";
        $dbconn->execute($query,array($sessionId));
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}

/**
 * PHP function to garbage collect session information
 * @private
 */
function xarSession__phpGC($maxlifetime)
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    $timeoutSetting = time() - ($GLOBALS['xarSession_systemArgs']['inactivityTimeout'] * 60);
    $bindvars = array();
    switch ($GLOBALS['xarSession_systemArgs']['securityLevel']) {
    case 'Low':
        // Low security - delete session info if user decided not to
        //                remember themself
        $where = "xar_remembersess = ? AND 
                  xar_lastused < ?";
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
        $dbconn->begin();
        $query = "DELETE FROM $sessioninfoTable WHERE $where";
        $dbconn->Execute($query,$bindvars);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}
?>
