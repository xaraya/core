<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file: Session Support
// ----------------------------------------------------------------------

/**
 * Initialise the Session Support
 * @returns bool
 * @return true on success
 */
function xarSession_init($args)
{
    global $xarSession_systemArgs;
    $xarSession_systemArgs = $args;

    // Session Support Tables
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables = array('session_info' => $systemPrefix . '_session_info');

    xarDB_importTables($tables);

    xarSession__setup($args);

    // First thing we do is ensure that there is no attempted pollution
    // of the session namespace (yes, we still need this for now)
    foreach($GLOBALS as $k=>$v) {
        if (preg_match('/^XARSV/', $k)) {
            xarCore_die('xarSession_init: Session Support initialisation failed.');
        }
    }

    // Start the session, this will call xarSession__phpRead, and
    // it will tell us if we need to start a new session or just
    // to continue the current session
    session_start();

    $sessionId = session_id();

    global $xarSession_isNewSession, $xarSession_ownerUserId, $xarSession_ipAddress;

    // TODO : add an admin option to re-activate this e.g. for
    //        Security Level "High" ?

    // Get  client IP addr
    $forwarded = xarServerGetVar('HTTP_X_FORWARDED_FOR');
    if (!empty($forwarded)) {
        $ipAddress = preg_replace('/,.*/', '', $forwarded);
    } else {
        $ipAddress = xarServerGetVar('REMOTE_ADDR');
    }

    if ($xarSession_isNewSession) {
        xarSession__new($sessionId, $ipAddress);

        // Generate a random number, used for
        // some authentication
        srand((double) microtime() * 1000000);

        xarSessionSetVar('rand', rand());
    } else {
        // same remark as in .71x branch : AOL, NZ and other ISPs don't
        // necessarily have a fixed IP (or a reliable X_FORWARDED_FOR)
        // if ($ipAddress == $xarSession_ipAddress) {
            xarSession__current($sessionId);
        // } else {
            // Mismatch - destroy the session
            //  session_destroy();
            //  xarRedirect('index.php');
            //  return false;
        // }
    }

    return true;
}

function xarSessionGetSecurityLevel()
{
    global $xarSession_systemArgs;

    return $xarSession_systemArgs['securityLevel'];
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
//    global $HTTP_SESSION_VARS;
    $var = 'XARSV' . $name;

// forget about $_SESSION for now - doesn't work for PHP 4.0.6
// + HTTP_SESSION_VARS is buggy on Windows for PHP 4.1.2
//    if (isset($HTTP_SESSION_VARS[$var])) {
//        return $HTTP_SESSION_VARS[$var];
    if (isset($GLOBALS[$var])) {
        return $GLOBALS[$var];
    } elseif (isset($GLOBALS['HTTP_SESSION_VARS'][$var])) {
        // another 'feature' for Windows
        $GLOBALS[$var] = $GLOBALS['HTTP_SESSION_VARS'][$var];
        return $GLOBALS['HTTP_SESSION_VARS'][$var];
    }

    return;
}

/**
 * Set a session variable
 * @param name name of the session variable to set
 * @param value value to set the named session variable
 */
function xarSessionSetVar($name, $value)
{
    if ($name == 'uid') {
        return false;
    }
//    global $HTTP_SESSION_VARS;
    $var = 'XARSV' . $name;

// forget about $_SESSION for now - doesn't work for PHP 4.0.6
// + HTTP_SESSION_VARS is buggy on Windows for PHP 4.1.2
//    $HTTP_SESSION_VARS[$var] = $value;
    $GLOBALS[$var] = $value;
    if (!session_is_registered($var)) {
        session_register($var);
    }

    return true;
}

/**
 * Delete a session variable
 * @param name name of the session variable to delete
 */
function xarSessionDelVar($name)
{
    if ($name == 'uid') {
        return false;
    }
//    global $HTTP_SESSION_VARS;
    $var = 'XARSV' . $name;

// forget about $_SESSION for now - doesn't work for PHP 4.0.6
// + HTTP_SESSION_VARS is buggy on Windows for PHP 4.1.2
//    if (isset($HTTP_SESSION_VARS[$var])) {
//        unset($HTTP_SESSION_VARS[$var]);
     if (isset($GLOBALS[$var]) || isset($GLOBALS['HTTP_SESSION_VARS'][$var])) {
        unset($GLOBALS[$var]);
        unset($GLOBALS['HTTP_SESSION_VARS'][$var]);
        // contrary to some of the PHP documentation, you *do* need this too !
        // http://www.php.net/manual/en/function.session-unregister.php is wrong
        // but http://www.php.net/manual/en/ref.session.php is right
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
    global $XARSVuid;

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];
    $query = "UPDATE $sessioninfoTable
              SET xar_uid = " . xarVarPrepForStore($userId) . ",
                  xar_remembersess = " . xarVarPrepForStore($rememberSession) . "
              WHERE xar_sessid = '" . xarVarPrepForStore(session_id()) . "'";
    $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    $XARSVuid = $userId;
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
 * @param $args['enableIntranetMode']
 * @param $args['inactivityTimeout']
 * @returns bool
 */
function xarSession__setup($args)
{
    $path = xarServerGetBaseURI();
    if (empty($path)) {
        $path = '/';
    }

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
    ini_set('session.name', 'XARAYASID');

    // Lifetime of our cookie
    switch ($args['securityLevel']) {
        case 'High':
            // Session lasts duration of browser
            $lifetime = 0;
            // Referer check
            $host = xarServerGetVar('HTTP_HOST');
            $host = preg_replace('/:.*/', '', $host);
            // this won't work for non-standard ports
            //ini_set('session.referer_check', "$host$path");
            // this should be customized for multi-server setups wanting to
            // share sessions
            ini_set('session.referer_check', $host);
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

    if ($args['enableIntranetMode'] == false) {
        // Cookie path
        // this should be customized for multi-server setups wanting to share
        // sessions
        ini_set('session.cookie_path', $path);

        // Cookie domain
        $domain = xarServerGetVar('HTTP_HOST');
        $domain = preg_replace('/:.*/', '', $domain);
        // this is only necessary for sharing sessions across multiple servers,
        // and should be configurable for multi-site setups
        // Example: .Xaraya.com for all *.Xaraya.com servers
        // Example: www.Xaraya.com for www.Xaraya.com and *.www.Xaraya.com
        //ini_set('session.cookie_domain', $domain);
    }


    // Garbage collection
    ini_set('session.gc_probability', 1);

    // Inactivity timeout for user sessions
    ini_set('session.gc_maxlifetime', $args['inactivityTimeout'] * 60);

    // Auto-start session
    ini_set('session.auto_start', 1);

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
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    // Touch the last used time
    $query = "UPDATE $sessioninfoTable
              SET xar_lastused = " . time() . "
              WHERE xar_sessid = '" . xarVarPrepForStore($sessionId) . "'";

    $result = $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

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
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    $query = "INSERT INTO $sessioninfoTable
                 (xar_sessid,
                  xar_ipaddr,
                  xar_uid,
                  xar_firstused,
                  xar_lastused)
              VALUES
                 ('" . xarVarPrepForStore($sessionId) . "',
                  '" . xarVarPrepForStore($ipAddress) . "',
                  0,
                  " . time() . ",
                  " . time() . ")";

    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

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
    global $xarSession_isNewSession, $xarSession_ipAddress;
    global $XARSVuid;

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    $query = "SELECT xar_uid,
                     xar_ipaddr,
                     xar_vars
              FROM $sessioninfoTable
              WHERE xar_sessid = '" . xarVarPrepForStore($sessionId) . "'";
    $result = $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

    if (!$result->EOF) {
        $xarSession_isNewSession = false;
        list($XARSVuid, $xarSession_ipAddress, $vars) = $result->fields;
    } else {
        $xarSession_isNewSession = true;
        // NOTE: <marco> Since it's useless to save the same information twice into
        // the session_info table, we use a little hack: $XARSVuid will appear to be
        // a session variable even if it's not registered as so!
        $XARSVuid = 0;
        $xarSession_ipAddress = '';
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
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    $query = "UPDATE $sessioninfoTable
              SET xar_vars = '" . xarVarPrepForStore($vars) . "'
              WHERE xar_sessid = '" . xarVarPrepForStore($sessionId) . "'";
    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

    return true;
}

/**
 * PHP function to destroy a session
 * @private
 */
function xarSession__phpDestroy($sessionId)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    $query = "DELETE FROM $sessioninfoTable
              WHERE xar_sessid = '" . xarVarPrepForStore($sessionId) . "'";
    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

    return true;
}

/**
 * PHP function to garbage collect session information
 * @private
 */
function xarSession__phpGC($maxlifetime)
{
    global $xarSession_systemArgs;

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    switch ($xarSession_systemArgs['securityLevel']) {
        case 'Low':
            // Low security - delete session info if user decided not to
            //                remember themself
            $where = "WHERE xar_remembersess = 0
                      AND xar_lastused < " . (time() - ($xarSession_systemArgs['inactivityTimeout'] * 60));
            break;
        case 'Medium':
            // Medium security - delete session info if session cookie has
            //                   expired or user decided not to remember
            //                   themself
            $where = "WHERE (xar_remembersess = 0
                        AND xar_lastused < " . (time() - ($xarSession_systemArgs['inactivityTimeout'] * 60)) . ")
                      OR xar_firstused < " . (time() - ($xarSession_systemArgs['duration'] * 86400));
            break;
        case 'High':
        default:
            // High security - delete session info if user is inactive
            $where = "WHERE xar_lastused < " . (time() - ($xarSession_systemArgs['inactivityTimeout'] * 60));
            break;
    }
    $query = "DELETE FROM $sessioninfoTable $where";
    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

    return true;
}

/**
 * Function to determine the client (browser, bot, ...)
 *
 * @return Boolean
 */
function xarSession__sniff()
{
//  sniff process
    include_once 'phpSniff.class.php';
    $client = new phpSniff('',0);
    $client->init();

/*
//  Get database setup (see xartables.php root)
    list($dbconn) = xarDBGetConn();
    $xartable      = xarDBGetTables();

//  set some variables used in the database call
    $uatable  =  $xartable['user_agent'];
    $uacolumn = &$xartable['user_agent_column'];

    $sql = "SELECT $uacolumn[id]
            FROM $uatable
            WHERE $uacolumn[agent] = '" . xarVarPrepForStore($client->get_property('ua')) . "'";
    $result = $dbconn->Execute($sql);

    if (!$result->EOF) {
        $uaid = $result->fields[0];
    } else {
        $dbconn->GenId('useragent')
        $insarr = array($dbconn->GenId($uatable), xarVarPrepForStore($client->get_property('ua')),
        $client->property('platform'), $client->property('os'),
                        $client->property('browser'), $client->property('version'));

        $insql = "INSERT INTO $uatable
                  VALUES ({$insarr[0]}, '{$insarr[1]}', '{$insarr[2]}',
                          '{$insarr[3]}', '{$insarr[4]}', '{$insarr[5]}', '', '')";
//      last 2 are reserved for caps and quirks, supported by the sniffers cvs-version
        $dbconn->Execute($insql);
        $uaid = $dbconn->PO_Insert_ID($uatable,$uacolumn['id']);
    }
*/

//  provide user agent details as session variables
    xarSessionSetVar("uaid", $uaid);
    xarSessionSetVar('browsername', $client->property('browser'));
    xarSessionSetVar('browserversion', $client->property('version'));
    xarSessionSetVar('osname', $client->property('platform'));
    xarSessionSetVar('osversion', $client->property('os'));
//  xarSessionSetVar('caps', $client->property('caps'));
//  xarSessionSetVar('quirks', $client->property('quirks'));
//  xarSessionSetVar('browserlang', $client->property('language'));

//  end of sniffin ... bark-bark
    return true;
}

?>