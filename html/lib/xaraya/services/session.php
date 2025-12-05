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

    /** @var class-string<SessionFacade> */
    private static $sessionClass = SessionHandler::class;
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
    /** @var array<string, mixed> */
    private array $args = [];
    protected bool $initialized = false;

    public static function setSessionClass($className): void
    {
        self::$sessionClass = $className;
    }

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
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

        $xar = $this->getServicesClass();
        $this->anonId = (int) $xar->config()->getVar('Site.User.AnonymousUID', 5);

        // Set up the session instance with current context
        $session = $this->newInstance();
        $this->setInstance($session);

        // Initialize the session
        $session->initialize();
        $this->initialized = true;
        return true;
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $xar = $this->getServicesClass();
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
    }

    public function isLoaded(): bool
    {
        return $this->initialized;
    }

    public function getInstance(): ?SessionFacade
    {
        // moved to static services class
        $instance = $this->getParent()->getSessionInstance();
        if (!isset($instance)) {
            // do *not* initialize session here - depends on the caller
            //$this->init($this->args);
        }
        return $instance;
    }

    public function setInstance(SessionFacade $instance): void
    {
        // moved to static services class
        $this->getParent()->setSessionInstance($instance);
    }

    public function newInstance(): SessionFacade
    {
        // Set up the session instance with current context
        return new self::$sessionClass($this->args, $this->getContext(), $this->getParent());
    }

    public function getId(?string $id = null): mixed
    {
        $instance = $this->getInstance();
        if (!isset($instance)) {
            return $id;
        }
        return $instance->getId($id);
    }

    public function getDefaultVar(string $varName): mixed
    {
        // no session means anonymous user by default
        if ($varName == 'role_id') {
            return $this->anonId;
        }
        // ignore templates and security try to get stuff in session
        if ($varName == 'navigationLocale') {
            $xar = $this->getServicesClass();
            return $xar->config()->getVar('Site.MLS.DefaultLocale');
        } elseif ($varName == 'privilegeset') {
            return null;
        }
        throw new SessionException('Session was not initialized to get ' . $varName);
    }

    /**
     * Get session variable
     */
    public function getVar(string $varName): mixed
    {
        $instance = $this->getInstance();
        if (!isset($instance)) {
            return $this->getDefaultVar($varName);
        }
        return $instance->getVar($varName);
    }

    /**
     * Set session variable
     */
    public function setVar(string $varName, mixed $value): bool
    {
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
    }

    /**
     * Delete session variable
     */
    public function delVar(string $varName): bool
    {
        if ($varName == 'role_id') {
            return false;
        }

        return $this->getInstance()?->delVar($varName) ?? false;
    }

    /**
     * Get current userId from session (if any) or anonymous userId or null
     */
    public function getUserId(): ?int
    {
        // @todo see UserContext::getUserId() for userId without session
        return $this->getInstance()?->getUserId();
    }

    /**
     * Get the anonymous userId or null if no session has been initialized
     */
    public function getAnonId(): ?int
    {
        return $this->anonId;
    }

    public function setAnonId(?int $anonId): void
    {
        $this->anonId = $anonId;
    }

    /**
     * Set user info
     */
    public function setUserInfo(int $userId, int $rememberSession): bool
    {
        return $this->getInstance()?->setUserInfo($userId, $rememberSession);
    }

    public function saveTime(int $lastused = 0): int
    {
        return $this->getInstance()?->saveTime($lastused);
    }

    public function getSecurityLevel(): string
    {
        return $this->securityLevel;
    }

    public function getTimeoutSetting(): int
    {
        $timeoutSetting = time() - ($this->inactivityTimeout * 60);
        return $timeoutSetting;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function clear($spared = []): bool
    {
        return $this->getInstance()?->clear() ?? false;
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
