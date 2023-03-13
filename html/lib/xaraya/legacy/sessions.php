<?php
/**
 * Session Support
 *
 * @package core\sessions\legacy
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
 * Get the configured security level
 *
 * @todo Is this used anywhere outside the session class itself?
 * @uses xarSession::getSecurityLevel()
 * @deprecated
 */
function xarSessionGetSecurityLevel()
{
    return xarSession::getSecurityLevel();
}

/*
 * Session variables here are a bit 'different'.  Because they sit in the
 * global namespace we use a couple of helper functions to give them their
 * own prefix, and also to force users to set new values for them if they
 * require.  This avoids blatant or accidental over-writing of session
 * variables.
 *
 * The old interface as wrappers for the class methods are here, see xarSession class
 * for the implementation
 */
/**
 * Legacy call
 * @uses xarSession::init()
 * @deprecated
 */
function xarSession_init(&$args)         { return xarSession::init($args); }
/**
 * Legacy call
 * @uses xarSession::getVar()
 * @deprecated
 */
function xarSessionGetVar($name)         { return xarSession::getVar($name); }
/**
 * Legacy call
 * @uses xarSession::setVar()
 * @deprecated
 */
function xarSessionSetVar($name, $value) { return xarSession::setVar($name, $value); }
/**
 * Legacy call
 * @uses xarSession::delVar()
 * @deprecated
 */
function xarSessionDelVar($name)         { return xarSession::delVar($name); }
/**
 * Legacy call
 * @uses xarSession::getId()
 * @deprecated
 */
function xarSessionGetId()               { return xarSession::getId(); }

// PROTECTED FUNCTIONS
/** mrb: if it's protected, how come roles uses it? */
/**
 * Legacy call
 * @uses xarSession::setUserInfo()
 * @deprecated
 */
function xarSession_setUserInfo($userId, $rememberSession)
{ return xarSession::setUserInfo($userId, $rememberSession); }
