<?php

/**
 * Session Support
 *
 * @package core\sessions
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
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

use Xaraya\Sessions\SessionInterface as SessionFacade;
use Xaraya\Services\SessionService;
use Xaraya\Services\xar;

/**
 * @deprecated 2.8.5 use xar::session() instead
 */
class xarSession
{
    protected static ?SessionService $sessionService = null;

    protected static function session(): SessionService
    {
        if (!isset(self::$sessionService)) {
            $xar = xar::getServicesClass();
            self::$sessionService = $xar->session();
        }
        return self::$sessionService;
    }

    /**
     * Initialise the Session Support (optional) - depends on the caller
     * This can only be called once for PHP session handler - use setInstance() if needed
     * @param array<string, mixed> $args
     * @param mixed $context
     * @return bool true
     */
    public static function init(array $args = [], $context = null)
    {
        // static cache for migration
        self::$sessionService = null;
        self::session()->init($args);
        // always start session here when called via legacy xarSession::init()
        return self::session()->start();
    }

    /**
     * Get session configuration
     * @return array<string, mixed>
     */
    public static function getConfig()
    {
        return self::session()->getConfig();
    }

    /**
     * Set the session class to use (instead of SessionHandler)
     * @param class-string $className
     * @return void
     */
    public static function setSessionClass($className)
    {
        self::session()->setSessionClass($className);
    }

    /**
     * Get the session class instance (optional)
     * @return ?SessionFacade
     */
    public static function getInstance()
    {
        return self::session()->getInstance();
    }

    /**
     * Set the session class instance
     * @param SessionFacade $instance
     * @return void
     */
    public static function setInstance($instance)
    {
        return self::session()->setInstance($instance);
    }

    /**
     * Summary of newInstance
     * @param mixed $context
     * @return SessionFacade
     */
    public static function newInstance($context = null)
    {
        return self::session()->newInstance();
    }

    /**
     * Get the session id if the session is initialized
     * @param ?string $id
     * @return string|bool|null
     */
    public static function getId($id = null)
    {
        return self::session()->getId($id);
    }

    /**
     * Get some default variables without session
     * @param string $name
     * @return mixed
     */
    public static function getDefaultVar($name)
    {
        return self::session()->getDefaultVar($name);
    }

    /**
     * Get a session variable
     *
     * @param string $name name of the session variable to get
     * @return mixed
     */
    public static function getVar($name)
    {
        return self::session()->getVar($name);
    }

    /**
     * Set a session variable
     * @param string $name name of the session variable to set
     * @param mixed $value value to set the named session variable
     * @return bool
     */
    public static function setVar($name, $value)
    {
        return self::session()->setVar($name, $value);
    }

    /**
     * Delete a session variable
     * @param string $name name of the session variable to delete
     * @return bool
     */
    public static function delVar($name)
    {
        return self::session()->delVar($name);
    }

    /**
     * Set user info
     * @param int $userId
     * @param int $rememberSession
     * @throws SQLException
     * @todo this seems a strange duck (only used in roles by the looks of it)
     * @return ?bool
     */
    public static function setUserInfo($userId, $rememberSession)
    {
        return self::session()->setUserInfo($userId, $rememberSession);
    }

    /**
     * When was this session last saved ?
     * @param int $lastused
     * @return ?int
     */
    public static function saveTime($lastused = 0)
    {
        return self::session()->saveTime($lastused);
    }

    /**
     * Get current userId from session (if any) or anonymous userId or null
     * @return ?int
     */
    public static function getUserId()
    {
        return self::session()->getUserId();
    }

    /**
     * Get the anonymous userId or null if no session has been initialized
     * @return ?int
     */
    public static function getAnonId()
    {
        return self::session()->getAnonId();
    }

    /**
     * Set the anonymous userId
     * @param int $anonId
     */
    public static function setAnonId($anonId)
    {
        return self::session()->setAnonId($anonId);
    }

    /**
     * Get the configured security level
     * @return string
     */
    public static function getSecurityLevel()
    {
        return self::session()->getSecurityLevel();
    }

    /**
     * Get Timeout Setting
     * @return int
     */
    public static function getTimeoutSetting()
    {
        return self::session()->getTimeoutSetting();
    }

    /**
     * Get Session Duration (In Days)
     * @return int
     */
    public static function getDuration()
    {
        return self::session()->getDuration();
    }

    /**
     * Clear all the sessions in the sessions table
     * @param array<mixed> $spared a list of roles IDs whose sessions are left untouched
     * @return bool
     */
    public static function clear($spared = [])
    {
        return self::session()->clear();
    }
}
