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

sys::import('xaraya.sessions.interface');
sys::import('xaraya.sessions.handler');
sys::import('xaraya.services.xar');
use Xaraya\Services\SessionService;
use Xaraya\Sessions\SessionInterface;
use Xaraya\Sessions\SessionHandler;
use Xaraya\Sessions\SessionException;
use Xaraya\Services\xar;

/**
 * @deprecated 2.8.5 use xar::session() instead
 */
class xarSession
{
    /** @var ?int */
    public static $anonId = null;     // Replacement for _XAR_ID_UNREGISTERED
    /** @var string */
    private static $securityLevel;
    /** @var int */
    private static $duration;
    /** @var int */
    private static $inactivityTimeout;
    //private static $cookieName;
    //private static $cookiePath;
    //private static $cookieDomain;
    //private static $refererCheck;
    /** @var class-string<SessionInterface> */
    private static $sessionClass = SessionHandler::class;
    /** @var array<string, mixed> */
    private static array $args = [];
    protected static bool $initialized = false;
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
     * @return boolean true
     */
    public static function init(array $args = [], $context = null)
    {
        // static cache for migration
        self::$sessionService = null;
        return self::session()->init($args);
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
     * @return ?SessionInterface
     */
    public static function getInstance()
    {
        return self::session()->getInstance();
    }

    /**
     * Set the session class instance
     * @param SessionInterface $instance
     * @return void
     */
    public static function setInstance($instance)
    {
        return self::session()->setInstance($instance);
    }

    /**
     * Summary of newInstance
     * @param mixed $context
     * @return SessionInterface
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
