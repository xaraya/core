<?php
// $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2001 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file: Session Support
// ----------------------------------------------------------------------

/**
 * Initialise the Session Support
 * @returns bool
 * @return true on success
 */
function pnSession_init($args)
{
    global $pnSession_systemArgs;
    $pnSession_systemArgs = $args;

    pnSession__setup($args);

    // First thing we do is ensure that there is no attempted pollution
    // of the session namespace (yes, we still need this for now)
    foreach($GLOBALS as $k=>$v) {
        if (preg_match('/^PNSV/', $k)) {
            die('pnSession_init: Session Support initialisation failed.');
        }
    }

    // Start the session, this will call pnSession__phpRead, and
    // it will tell us if we need to start a new session or just
    // to continue the current session
    session_start();

    $sessionId = session_id();

    global $pnSession_isNewSession, $pnSession_ownerUserId, $pnSession_ipAddress;

    // TODO : add an admin option to re-activate this e.g. for
    //        Security Level "High" ?

    // Get  client IP addr
    $forwarded = pnServerGetVar('HTTP_X_FORWARDED_FOR');
    if (!empty($forwarded)) {
        $ipAddress = preg_replace('/,.*/', '', $forwarded);
    } else {
        $ipAddress = pnServerGetVar('REMOTE_ADDR');
    }

    if ($pnSession_isNewSession) {
        pnSession__new($sessionId, $ipAddress);

        // Generate a random number, used for
        // some authentication
        srand((double) microtime() * 1000000);

        pnSessionSetVar('rand', rand());
    } else {
        // same remark as in .71x branch : AOL, NZ and other ISPs don't
        // necessarily have a fixed IP (or a reliable X_FORWARDED_FOR)
        // if ($ipAddress == $pnSession_ipAddress) {
            pnSession__current($sessionId);
        // } else {
            // Mismatch - destroy the session
            //  session_destroy();
            //  pnRedirect('index.php');
            //  return false;
        // }
    }

    return true;
}

function pnSessionGetSecurityLevel()
{
    global $pnSession_systemArgs;

    return $pnSession_systemArgs['securityLevel'];
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
function pnSessionGetVar($name)
{
//    global $HTTP_SESSION_VARS;
    $var = 'PNSV' . $name;

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
function pnSessionSetVar($name, $value)
{
    if ($name == 'uid') {
        return false;
    }
//    global $HTTP_SESSION_VARS;
    $var = 'PNSV' . $name;

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
function pnSessionDelVar($name)
{
    if ($name == 'uid') {
        return false;
    }
//    global $HTTP_SESSION_VARS;
    $var = 'PNSV' . $name;

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

function pnSessionGetId()
{
    return session_id();
}

// PROTECTED FUNCTIONS

function pnSession_setUserInfo($userId, $rememberSession)
{
    global $PNSVuid;

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sessioninfoTable = $pntable['session_info'];
    $query = "UPDATE $sessioninfoTable
              SET pn_uid = " . pnVarPrepForStore($userId) . ",
                  pn_remembersess = " . pnVarPrepForStore($rememberSession) . "
              WHERE pn_sessid = '" . pnVarPrepForStore(session_id()) . "'";
    $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    $PNSVuid = $userId;
    return true;
}

function pnSession_close()
{
    session_write_close();
}

// PRIVATE FUNCTIONS

/**
 * Set up session handling
 *
 * Set all PHP options for PostNuke session handling
 */
function pnSession__setup($args)
{
    $path = pnServerGetBaseURI();
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
    ini_set('session.name', 'POSTNUKESID');

    // Lifetime of our cookie
    switch ($args['securityLevel']) {
        case 'High':
            // Session lasts duration of browser
            $lifetime = 0;
            // Referer check
            $host = pnServerGetVar('HTTP_HOST');
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
        $domain = pnServerGetVar('HTTP_HOST');
        $domain = preg_replace('/:.*/', '', $domain);
        // this is only necessary for sharing sessions across multiple servers,
        // and should be configurable for multi-site setups
        // Example: .postnuke.com for all *.postnuke.com servers
        // Example: www.postnuke.com for www.postnuke.com and *.www.postnuke.com
        //ini_set('session.cookie_domain', $domain);
    }


    // Garbage collection
    ini_set('session.gc_probability', 1);

    // Inactivity timeout for user sessions
    ini_set('session.gc_maxlifetime', $args['inactivityTimeout'] * 60);

    // Auto-start session
    ini_set('session.auto_start', 1);

    // Session handlers
    session_set_save_handler("pnSession__phpOpen",
                             "pnSession__phpClose",
                             "pnSession__phpRead",
                             "pnSession__phpWrite",
                             "pnSession__phpDestroy",
                             "pnSession__phpGC");
    return true;
}

/**
 * Continue a current session
 * @private
 * @param sessionId the session ID
 */
function pnSession__current($sessionId)
{
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sessioninfoTable = $pntable['session_info'];

    // Touch the last used time
    $query = "UPDATE $sessioninfoTable
              SET pn_lastused = " . time() . "
              WHERE pn_sessid = '" . pnVarPrepForStore($sessionId) . "'";

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
function pnSession__new($sessionId, $ipAddress)
{
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sessioninfoTable = $pntable['session_info'];

    $query = "INSERT INTO $sessioninfoTable
                 (pn_sessid,
                  pn_ipaddr,
                  pn_uid,
                  pn_firstused,
                  pn_lastused)
              VALUES
                 ('" . pnVarPrepForStore($sessionId) . "',
                  '" . pnVarPrepForStore($ipAddress) . "',
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
function pnSession__phpOpen($path, $name)
{
    // Nothing to do - database opened elsewhere
    return true;
}

/**
 * PHP function to close the session
 * @private
 */
function pnSession__phpClose()
{
    // Nothing to do - database closed elsewhere
    return true;
}

/**
 * PHP function to read a set of session variables
 * @private
 */
function pnSession__phpRead($sessionId)
{
    global $pnSession_isNewSession, $pnSession_ipAddress;
    global $PNSVuid;

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sessioninfoTable = $pntable['session_info'];

    $query = "SELECT pn_uid,
                     pn_ipaddr,
                     pn_vars
              FROM $sessioninfoTable
              WHERE pn_sessid = '" . pnVarPrepForStore($sessionId) . "'";
    $result = $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

    if (!$result->EOF) {
        $pnSession_isNewSession = false;
        list($PNSVuid, $pnSession_ipAddress, $vars) = $result->fields;
    } else {
        $pnSession_isNewSession = true;
        // NOTE: <marco> Since it's useless to save the same information twice into
        // the session_info table, we use a little hack: $PNSVuid will appear to be
        // a session variable even if it's not registered as so!
        $PNSVuid = 0;
        $pnSession_ipAddress = '';
        $vars = '';
    }
    $result->Close();

    return $vars;
}

/**
 * PHP function to write a set of session variables
 * @private
 */
function pnSession__phpWrite($sessionId, $vars)
{
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sessioninfoTable = $pntable['session_info'];

    $query = "UPDATE $sessioninfoTable
              SET pn_vars = '" . pnVarPrepForStore($vars) . "'
              WHERE pn_sessid = '" . pnVarPrepForStore($sessionId) . "'";
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
function pnSession__phpDestroy($sessionId)
{
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sessioninfoTable = $pntable['session_info'];

    $query = "DELETE FROM $sessioninfoTable
              WHERE pn_sessid = '" . pnVarPrepForStore($sessionId) . "'";
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
function pnSession__phpGC($maxlifetime)
{
    global $pnSession_systemArgs;

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sessioninfoTable = $pntable['session_info'];

    switch ($pnSession_systemArgs['securityLevel']) {
        case 'Low':
            // Low security - delete session info if user decided not to
            //                remember themself
            $where = "WHERE pn_remembersess = 0
                      AND pn_lastused < " . (time() - ($pnSession_systemArgs['inactivityTimeout'] * 60));
            break;
        case 'Medium':
            // Medium security - delete session info if session cookie has
            //                   expired or user decided not to remember
            //                   themself
            $where = "WHERE (pn_remembersess = 0
                        AND pn_lastused < " . (time() - ($pnSession_systemArgs['inactivityTimeout'] * 60)) . ")
                      OR pn_firstused < " . (time() - ($pnSession_systemArgs['duration'] * 86400));
            break;
        case 'High':
        default:
            // High security - delete session info if user is inactive
            $where = "WHERE pn_lastused < " . (time() - ($pnSession_systemArgs['inactivityTimeout'] * 60));
            break;
    }
    $query = "DELETE FROM $sessioninfoTable $where";
    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

    return true;
}

?>
