<?php

/**
 * Session available via methods (WIP)
 *
 * @todo align with Xaraya\Sessions\SessionInterface
 * @todo integrate SessionHandler vs. SessionContext options
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Sessions\SessionInterface as SessionFacade;
use Xaraya\Sessions\SessionHandler;
use Xaraya\Sessions\SessionException;
use xarSession;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via SessionTrait
 */
interface SessionInterface extends ServiceInterface
{
    public const SLICE = 'session2';

    public static function setSessionClass($className): void;
    public function init(array $config = []): bool;
    public function getConfig(): array;
    public function getInstance(): ?SessionFacade;
    public function setInstance(SessionFacade $instance): void;
    public function newInstance(): SessionFacade;
    public function getId(?string $id = null): mixed;
    public function getDefaultVar(string $varName): mixed;
    public function getVar(string $varName): mixed;
    public function setVar(string $varName, mixed $value): bool;
    public function delVar(string $varName): bool;
    public function getUserId(): ?int;
    public function getAnonId(): ?int;
    public function setAnonId(?int $anonId): void;
    public function setUserInfo(int $userId, int $rememberSession): bool;
    public function saveTime(int $lastused = 0): int;
    public function getSecurityLevel(): string;
    public function getTimeoutSetting(): int;
    public function getDuration(): int;
    public function clear($spared = []): bool;
}

/**
 * Session available via methods
 */
trait SessionTrait
{
    use ServiceTrait;

    /** @var ?int */
    public $anonId = null;     // Replacement for _XAR_ID_UNREGISTERED
    /** @var string */
    private $securityLevel;
    /** @var int */
    private $duration;
    /** @var int */
    private $inactivityTimeout;
    //private $cookieName;
    //private $cookiePath;
    //private $cookieDomain;
    //private $refererCheck;
    /** @var class-string<SessionFacade> */
    private static $sessionClass = SessionHandler::class;
    /** @var array<string, mixed> */
    private array $args = [];
    protected bool $initialized = false;

    public static function setSessionClass($className): void
    {
        // --- LEGACY METHOD BODY ---
        self::$sessionClass = $className;
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        // --- LEGACY METHOD BODY ---
        if (empty($config)) {
            if (!empty($this->initialized)) {
                return true;
            }
            $config = $this->getConfig();
        }
        $this->securityLevel = $config['securityLevel'];
        $this->duration = $config['duration'];
        $this->inactivityTimeout = $config['inactivityTimeout'];
        //$this->cookieName = $config['cookieName'];
        //$this->cookiePath = $config['cookiePath'];
        //$this->cookieDomain = $config['cookieDomain'];
        //$this->refererCheck = $config['refererCheck'));
        //self::$sessionClass = $config['sessionClass'] ?? SessionHandler::class;
        $this->args = $config;

        $xar = $this->getParent();
        $this->anonId = (int) $xar->config()->getVar('Site.User.AnonymousUID', 5);

        // Set up the session instance with current context
        $session = $this->newInstance();
        $this->setInstance($session);

        // Initialize the session
        $session->initialize();
        $this->initialized = true;
        return true;
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $xar = $this->getParent();
        // --- LEGACY METHOD BODY ---
        $systemArgs = [
            'securityLevel'     => $xar->config()->getVar('Site.Session.SecurityLevel'),
            'duration'          => $xar->config()->getVar('Site.Session.Duration'),
            'inactivityTimeout' => $xar->config()->getVar('Site.Session.InactivityTimeout'),
            'cookieName'        => $xar->config()->getVar('Site.Session.CookieName'),
            'cookiePath'        => $xar->config()->getVar('Site.Session.CookiePath'),
            'cookieDomain'      => $xar->config()->getVar('Site.Session.CookieDomain'),
            'refererCheck'      => $xar->config()->getVar('Site.Session.RefererCheck'),
            //'sessionClass'      => $xar->config()->getVar('Site.Session.HandlerClass'),
        ];
        return $systemArgs;
        // --- END LEGACY METHOD BODY ---
    }

    public function getInstance(): ?SessionFacade
    {
        // --- LEGACY METHOD BODY ---
        // moved to static services class
        $instance = $this->getParent()->getSessionInstance();
        if (!isset($instance)) {
            // do *not* initialize session here - depends on the caller
            //$this->init($this->args);
        }
        return $instance;
        // --- END LEGACY METHOD BODY ---
    }

    public function setInstance(SessionFacade $instance): void
    {
        // --- LEGACY METHOD BODY ---
        // moved to static services class
        $this->getParent()->setSessionInstance($instance);
        // --- END LEGACY METHOD BODY ---
    }

    public function newInstance(): SessionFacade
    {
        // Set up the session instance with current context
        // --- LEGACY METHOD BODY ---
        return new self::$sessionClass($this->args, $this->getContext());
        // --- END LEGACY METHOD BODY ---
    }

    public function getId(?string $id = null): mixed
    {
        // --- LEGACY METHOD BODY ---
        $instance = $this->getInstance();
        if (!isset($instance)) {
            return $id;
        }
        return $instance->getId($id);
        // --- END LEGACY METHOD BODY ---
    }

    public function getDefaultVar(string $varName): mixed
    {
        // --- LEGACY METHOD BODY ---
        // no session means anonymous user by default
        if ($varName == 'role_id') {
            return $this->anonId;
        }
        // ignore templates and security try to get stuff in session
        if ($varName == 'navigationLocale') {
            $xar = $this->getParent();
            return $xar->config()->getVar('Site.MLS.DefaultLocale');
        } elseif ($varName == 'privilegeset') {
            return null;
        }
        throw new SessionException('Session was not initialized to get ' . $varName);
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Get session variable
     */
    public function getVar(string $varName): mixed
    {
        // --- LEGACY METHOD BODY ---
        $instance = $this->getInstance();
        if (!isset($instance)) {
            return $this->getDefaultVar($varName);
        }
        return $instance->getVar($varName);
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Set session variable
     */
    public function setVar(string $varName, mixed $value): bool
    {
        // --- LEGACY METHOD BODY ---
        assert(!is_null($value));
        // security checks : do not allow to set the id or mess with the session serialization
        if ($varName == 'role_id' || strpos($varName, '|') !== false) {
            return false;
        }

        $instance = $this->getInstance();
        if (!isset($instance)) {
            // ignore templates and security try to save stuff in session
            if ($varName == 'navigationLocale' || $varName == 'privilegeset') {
                return false;
            }
            throw new SessionException('Session was not initialized to set ' . $varName);
        }
        return $instance->setVar($varName, $value);
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Delete session variable
     */
    public function delVar(string $varName): bool
    {
        // --- LEGACY METHOD BODY ---
        if ($varName == 'role_id') {
            return false;
        }

        return $this->getInstance()?->delVar($varName) ?? false;
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Get current userId from session (if any) or anonymous userId or null
     */
    public function getUserId(): ?int
    {
        // --- LEGACY METHOD BODY ---
        // @todo see UserContext::getUserId() for userId without session
        return $this->getInstance()?->getUserId();
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Get the anonymous userId or null if no session has been initialized
     */
    public function getAnonId(): ?int
    {
        // --- LEGACY METHOD BODY ---
        return $this->anonId;
        // --- END LEGACY METHOD BODY ---
    }

    public function setAnonId(?int $anonId): void
    {
        // --- LEGACY METHOD BODY ---
        $this->anonId = $anonId;
        // --- END LEGACY METHOD BODY ---
    }

    /**
     * Set user info
     */
    public function setUserInfo(int $userId, int $rememberSession): bool
    {
        // --- LEGACY METHOD BODY ---
        return $this->getInstance()?->setUserInfo($userId, $rememberSession);
        // --- END LEGACY METHOD BODY ---
    }

    public function saveTime(int $lastused = 0): int
    {
        // --- LEGACY METHOD BODY ---
        return $this->getInstance()?->saveTime($lastused);
        // --- END LEGACY METHOD BODY ---
    }

    public function getSecurityLevel(): string
    {
        // --- LEGACY METHOD BODY ---
        return $this->securityLevel;
        // --- END LEGACY METHOD BODY ---
    }

    public function getTimeoutSetting(): int
    {
        // --- LEGACY METHOD BODY ---
        $timeoutSetting = time() - ($this->inactivityTimeout * 60);
        return $timeoutSetting;
        // --- END LEGACY METHOD BODY ---
    }

    public function getDuration(): int
    {
        // --- LEGACY METHOD BODY ---
        return $this->duration;
        // --- END LEGACY METHOD BODY ---
    }

    public function clear($spared = []): bool
    {
        // --- LEGACY METHOD BODY ---
        return $this->getInstance()?->clear() ?? false;
        // --- END LEGACY METHOD BODY ---
    }
}

/**
 * Access xarSession::* Session methods (getVar, setVar, ...)
 *
 * Available methods:
 * - getVar()
 * - setVar()
 * - delVar()
 * - getUserId()
 * - getAnonId()
 * - ...
 *
 */
class SessionService implements SessionInterface
{
    use SessionTrait;
}
