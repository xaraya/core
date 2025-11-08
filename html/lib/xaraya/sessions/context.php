<?php

/**
 * @package core\context
 * @subpackage context
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Context;

use Xaraya\Sessions\SessionInterface;
use Xaraya\Sessions\VirtualSession;
use Xaraya\Sessions\Storage\SessionCacheStorage;
use Xaraya\Sessions\Storage\SessionStorageInterface;
use Xaraya\Services\xar;
use sys;
use RuntimeException;

sys::import('xaraya.sessions.interface');
sys::import('xaraya.sessions.virtual');
sys::import('xaraya.sessions.storage');
sys::import('xaraya.context.contexttrait');

/**
 * Session instance with context for use with xar::session()->setInstance()
 *
 * This uses a virtual session object from context, to replace $_SESSION
 * and bypass the default (global) PHP session handling by SessionHandler()
 *
 * Sessions can be saved in persistent database storage by replacing the
 * $storageClass with SessionDatabaseStorage instead of SessionCacheStorage
 * @todo decide when to save the session in the request/response cycle
 */
class SessionContext implements ContextInterface, SessionInterface
{
    use ContextTrait;

    /** @var class-string<SessionStorageInterface> */
    private static $storageClass = SessionCacheStorage::class;
    /** @var ?SessionStorageInterface */
    private $storage = null;
    /** @var array<string, mixed> */
    private array $args = [];
    private ?string $sessionId = null;
    private bool $isUpdated = false;
    private ?int $lastSaved = null;

    /**
     * Set the storage class to use (instead of SessionCacheStorage)
     * @param class-string $className
     * @return void
     */
    public static function setStorageClass($className)
    {
        self::$storageClass = $className;
    }

    /**
     * Constructor for the session handler
     * @param array<string, mixed> $args not by reference anymore
     * @param ?Context<string, mixed> $context
     * @return void
     **/
    public function __construct($args = [], $context = null)
    {
        $this->args = $args;
        $this->context = $context;
    }

    /**
     * Destructor for the session handler
     * @return void
     **/
    public function __destruct()
    {
        // Make sure we write dirty data before we lose this object
        $this->save();
    }

    /**
     * Initialize the session after setup
     * @return bool
     */
    public function initialize()
    {
        // always get storage here when xar::session()->init() is called
        $this->getStorage();
        // start session based on cookie here
        return $this->start();
    }

    public function start()
    {
        // check if we already have a context to work with
        if (!isset($this->context)) {
            return false;
        }
        return $this->findSession();
    }

    public function findSession()
    {
        // use session cookie here - see UserContext::checkCookie()
        $sessionId = RequestContext::getSessionCookie($this->context);
        if (empty($sessionId)) {
            // @todo create new sessionId here?
            return false;
        }
        $serverVars = $this->context['server'] ?? [];
        $ipAddress = $serverVars['REMOTE_ADDR'] ?? '-';
        $session = $this->getStorage()->lookup($sessionId, $ipAddress);
        $this->context['session'] = $session;
        if (empty($session)) {
            // @todo create dummy virtual session?
            return false;
        }
        return true;
    }

    public function getContext()
    {
        if (!isset($this->context)) {
            // $this->context = new Context(['source' => __CLASS__]);
            // Use context from static services class here
            $this->context = xar::getServicesClass()->getContext();
            throw new RuntimeException('Session context is not initialized yet');
        }
        return $this->context;
    }

    /**
     * Get storage on demand (lazy load)
     * @return SessionStorageInterface
     */
    public function getStorage()
    {
        $this->storage ??= new self::$storageClass($this->args);
        return $this->storage;
    }

    /**
     * Get current sessionId from context
     * @return string|null
     */
    public function getSessionId()
    {
        if (!isset($this->sessionId)) {
            $session = $this->getSession();
            if (empty($session)) {
                return null;
            }
            $this->sessionId = $session->getSessionId();
        }
        return $this->sessionId;
    }

    /**
     * Get virtual session object from context (if any)
     * @return VirtualSession|null
     */
    public function getSession()
    {
        // ok if we don't have a context or session here yet for non-standard entrypoint - see xar::user()->init()
        return $this->getContext()?->getSession();
    }

    /**
     * Actions to take before handling request
     * @return void
     */
    public function before()
    {
        // actions to take before handling request
    }

    /**
     * Actions to take after handling request
     * @return void
     */
    public function after()
    {
        // actions to take after handling request
    }

    /**
     * Get (or set) the session id
     * @param ?string $id
     * @return string|bool
     */
    public function getId($id = null)
    {
        if (isset($id)) {
            $this->sessionId = $id;
            // @todo update sessionId in session here?
            $session = $this->getSession();
            if (!empty($session)) {
                $session->setSessionId($id);
            }
        }
        return $this->getSessionId() ?? false;
    }

    /**
     * Get a session variable
     * @param string $name name of the session variable to get
     * @return mixed
     */
    public function getVar($name)
    {
        $session = $this->getSession();
        if (empty($session)) {
            // @todo some default variables without session
            return xar::session()->getDefaultVar($name);
        }
        if (array_key_exists($name, $session->vars)) {
            return $session->vars[$name];
        }
        if ($name == 'role_id') {
            // @todo look up userId or return xar::session()->getAnonId()
            return $session->getUserId();
        }
        return null;
    }

    /**
     * Set a session variable
     * @param string $name name of the session variable to set
     * @param mixed $value value to set the named session variable
     * @return bool
     */
    public function setVar($name, $value)
    {
        $session = $this->getSession();
        if (empty($session)) {
            return false;
        }
        if (!array_key_exists($name, $session->vars) || $session->vars[$name] !== $value) {
            $this->isUpdated = true;
        }
        $session->vars[$name] = $value;
        return true;
    }

    /**
     * Delete a session variable
     * @param string $name name of the session variable to delete
     * @return bool
     */
    public function delVar($name)
    {
        $session = $this->getSession();
        if (empty($session)) {
            return false;
        }
        if (array_key_exists($name, $session->vars)) {
            unset($session->vars[$name]);
            $this->isUpdated = true;
        }
        return true;
    }

    /**
     * Set user info
     * @param int $userId
     * @param int $rememberSession
     * @todo this seems a strange duck (only used in roles by the looks of it)
     * @return bool
     */
    public function setUserInfo($userId, $rememberSession)
    {
        $session = $this->getSession();
        if (empty($session)) {
            return false;
        }
        $session->setUserId($userId);
        $session->vars['remember'] = $rememberSession;
        $this->isUpdated = true;
        return true;
    }

    /**
     * When was this session last saved ?
     * @param int $lastused
     * @return int
     */
    public function saveTime($lastused = 0)
    {
        // initialize saveTime if necessary
        if (!isset($this->lastSaved) || !empty($lastused)) {
            $this->lastSaved = (int) $lastused;
        }
        return $this->lastSaved;
    }

    /**
     * Get current userId from session (if any)
     * @return int|null
     */
    public function getUserId()
    {
        return $this->getVar('role_id');
    }

    /**
     * Get current session variables
     * @return ?array<string, mixed>
     */
    public function getVars()
    {
        $session = $this->getSession();
        if (empty($session)) {
            return null;
        }
        return $session->vars;
    }

    /**
     * Unset current session variables
     * @return void
     */
    public function unsetVars()
    {
        $session = $this->getSession();
        if (empty($session)) {
            return;
        }
        $session->setUserId(0);
        $session->vars = [];
        // @todo do we want to update or delete here?
        //$this->isUpdated = true;
        $this->getStorage()->delete($session);
    }

    /**
     * Clear all the sessions in the sessions table
     * @param array<mixed> $spared a list of roles IDs whose sessions are left untouched
     * @return bool
     */
    public function clear($spared = [])
    {
        $session = $this->getSession();
        if (empty($session)) {
            return false;
        }
        // @todo what do we want to do here?
        return true;
    }

    /**
     * Start session with context, sessionId and userId
     *
     * @param Context<string, mixed> $context
     * @return VirtualSession
     * @see \Xaraya\Context\UserContext::initSession()
     */
    public function startSession(Context $context, string $sessionId, int $userId = 0, string $ipAddress = '')
    {
        // @todo do we want to lookup or register RemoteUser: or AuthToken: sessions in storage here?
        $session = $this->getStorage()->lookup($sessionId, $ipAddress);
        if (!isset($session)) {
            $session = new VirtualSession($sessionId, $userId, $ipAddress, time(), []);
            $this->getStorage()->register($session);
            $session->isNew = true;
        } else {
            $session->setUserId($userId);
            $session->isNew = false;
        }
        $context['session'] = $session;
        $this->setContext($context);

        return $session;
    }

    /**
     * Save session to storage if updated
     * @return bool
     */
    public function save()
    {
        $session = $this->getSession();
        if (empty($session)) {
            return false;
        }
        // do we want to save session if storage is not initialized here?
        if ($this->isUpdated && !empty($this->storage)) {
            $this->getStorage()->update($session);
            $this->isUpdated = false;
        }
        $this->saveTime($session->lastUsed);
        return true;
    }
}
