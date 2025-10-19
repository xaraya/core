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

sys::import('xaraya.sessions.interface');
sys::import('xaraya.sessions.handler');
sys::import('xaraya.facades.config');
use Xaraya\Sessions\SessionInterface;
use Xaraya\Sessions\SessionHandler;
use Xaraya\Sessions\SessionException;
use Xaraya\Facades\xarConfig3;

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
    /** @var ?SessionInterface */
    private static $instance;
    /** @var ?int */
    private static $lastSaved;
    /** @var class-string<SessionInterface> */
    private static $sessionClass = SessionHandler::class;

    /**
     * Initialise the Session Support
     * This can only be called once for PHP session handler - use setInstance() if needed
     * @param array<string, mixed> $args
     * @param mixed $context
     * @return boolean true
     */
    public static function init(array $args = [], $context = null)
    {
        if (empty($args)) {
            if (!empty(self::$instance)) {
                return true;
            }
            $args = self::getConfig();
        }
        self::$securityLevel = $args['securityLevel'];
        self::$duration = $args['duration'];
        self::$inactivityTimeout = $args['inactivityTimeout'];
        //self::$cookieName = $args['cookieName'];
        //self::$cookiePath = $args['cookiePath'];
        //self::$cookieDomain = $args['cookieDomain'];
        //self::$refererCheck = $args['refererCheck'));
        //self::sessionClass = $args['sessionClass'] ?? SessionHandler::class;

        self::$anonId = (int) xarConfig3::getVar('Site.User.AnonymousUID', 5);

        // Set up the session object with context
        $session = new self::$sessionClass($args, $context);
        self::setInstance($session);

        // Initialize the session
        $session->initialize();
        return true;
    }

    /**
     * Get session configuration
     * @return array<string, mixed>
     */
    public static function getConfig()
    {
        $systemArgs = [
            'securityLevel'     => xarConfig3::getVar('Site.Session.SecurityLevel'),
            'duration'          => xarConfig3::getVar('Site.Session.Duration'),
            'inactivityTimeout' => xarConfig3::getVar('Site.Session.InactivityTimeout'),
            'cookieName'        => xarConfig3::getVar('Site.Session.CookieName'),
            'cookiePath'        => xarConfig3::getVar('Site.Session.CookiePath'),
            'cookieDomain'      => xarConfig3::getVar('Site.Session.CookieDomain'),
            'refererCheck'      => xarConfig3::getVar('Site.Session.RefererCheck'),
            //'sessionClass'      => xarConfig3::getVar('Site.Session.HandlerClass'),
        ];
        return $systemArgs;
    }

    /**
     * Set the session class to use (instead of SessionHandler)
     * @param class-string $className
     * @return void
     */
    public static function setSessionClass($className)
    {
        self::$sessionClass = $className;
    }

    /**
     * Get the session class instance
     * @return ?SessionInterface
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            // do *not* initialize session here - depends on the caller
            //self::init();
        }
        return self::$instance;
    }

    /**
     * Set the session class instance
     * @param SessionInterface $instance
     * @return void
     */
    public static function setInstance($instance)
    {
        self::$instance = $instance;
    }

    /**
     * Get the session id if the session is initialized
     * @param ?string $id
     * @return string|bool|null
     */
    public static function getId($id = null)
    {
        if (!isset(self::$instance)) {
            return $id;
        }
        return self::$instance->getId($id);
    }

    /**
     * Get some default variables without session
     * @param string $name
     * @return mixed
     */
    public static function getDefaultVar($name)
    {
        // no session means anonymous user by default
        if ($name == 'role_id') {
            return self::$anonId;
        }
        // ignore templates and security try to get stuff in session
        if ($name == 'navigationLocale') {
            return xarConfig3::getVar('Site.MLS.DefaultLocale');
        } elseif ($name == 'privilegeset') {
            return null;
        }
        throw new SessionException('Session was not initialized to get ' . $name);
    }

    /**
     * Get a session variable
     *
     * @param string $name name of the session variable to get
     * @return mixed
     */
    public static function getVar($name)
    {
        if (!isset(self::$instance)) {
            return self::getDefaultVar($name);
        }
        return self::$instance->getVar($name);
    }

    /**
     * Set a session variable
     * @param string $name name of the session variable to set
     * @param mixed $value value to set the named session variable
     * @return bool
     */
    public static function setVar($name, $value)
    {
        assert(!is_null($value));
        // security checks : do not allow to set the id or mess with the session serialization
        if ($name == 'role_id' || strpos($name, '|') !== false) {
            return false;
        }

        if (!isset(self::$instance)) {
            // ignore templates and security try to save stuff in session
            if ($name == 'navigationLocale' || $name == 'privilegeset') {
                return false;
            }
            throw new SessionException('Session was not initialized to set ' . $name);
        }
        return self::$instance->setVar($name, $value);
    }

    /**
     * Delete a session variable
     * @param string $name name of the session variable to delete
     * @return bool
     */
    public static function delVar($name)
    {
        if ($name == 'role_id') {
            return false;
        }

        return self::$instance->delVar($name);
    }

    /**
     * Set user info
     * @param int $userId
     * @param int $rememberSession
     * @throws SQLException
     * @todo this seems a strange duck (only used in roles by the looks of it)
     * @return bool
     */
    public static function setUserInfo($userId, $rememberSession)
    {
        return self::$instance->setUserInfo($userId, $rememberSession);
    }

    /**
     * When was this session last saved ?
     * @param int $lastused
     * @return ?int
     */
    public static function saveTime($lastused = 0)
    {
        return self::getInstance()?->saveTime($lastused);
    }

    /**
     * Get current userId from session (if any) or anonymous userId or null
     * @return ?int
     */
    public static function getUserId()
    {
        // @todo see UserContext::getUserId() for userId without session
        return self::getInstance()?->getUserId();
    }

    /**
     * Get the anonymous userId or null if no session has been initialized
     * @return ?int
     */
    public static function getAnonId()
    {
        return self::$anonId;
    }

    /**
     * Set the anonymous userId
     * @param int $anonId
     */
    public static function setAnonId($anonId)
    {
        self::$anonId = $anonId;
    }

    /**
     * Get the configured security level
     * @return string
     */
    public static function getSecurityLevel()
    {
        return self::$securityLevel;
    }

    /**
     * Get Timeout Setting
     * @return int
     */
    public static function getTimeoutSetting()
    {
        $timeoutSetting = time() - (self::$inactivityTimeout * 60);
        return $timeoutSetting;
    }

    /**
     * Get Session Duration (In Days)
     * @return int
     */
    public static function getDuration()
    {
        return self::$duration;
    }

    /**
     * Clear all the sessions in the sessions table
     * @param array<mixed> $spared a list of roles IDs whose sessions are left untouched
     * @return bool
     */
    public static function clear($spared = [])
    {
        return self::$instance->clear();
    }
}
