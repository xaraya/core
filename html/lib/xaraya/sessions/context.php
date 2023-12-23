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

use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;
use Xaraya\Sessions\SessionInterface;
use xarSession;
use sys;

sys::import('xaraya.sessions.interface');
sys::import('xaraya.traits.contexttrait');

/**
 * Session instance with context for use with xarSession::setInstance()
 */
class SessionContext implements ContextInterface, SessionInterface
{
    use ContextTrait;

    /** @var array<string, mixed> */
    private array $args = [];
    private ?string $sessionId = null;
    private int $length = 32;

    /**
     * Constructor for the session handler
     * @param array<string, mixed> $args not by reference anymore
     * @uses xarSession::setInstance()
     * @return void
     **/
    public function __construct($args = [])
    {
        $this->args = $args;
        xarSession::setInstance($this);
    }

    /**
     * Initialize the session after setup
     * @return bool
     */
    public function initialize()
    {
        if (!isset($this->context)) {
            $this->context = new Context();
            //$sessionId = bin2hex(random_bytes($this->length));
            //$session = new VirtualSession($sessionId, $userId);
            //$this->context->offsetSet('session', $session);
        }
        return true;
    }

    /**
     * Get current sessionId from context
     * @return string|null
     */
    public function getSessionId()
    {
        if (!isset($this->sessionId)) {
            $session = $this->getContext()?->getSession();
            if (empty($session)) {
                return null;
            }
            $this->sessionId = $session->getSessionId();
        }
        return $this->sessionId;
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
            $session = $this->getContext()?->getSession();
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
        $session = $this->getContext()?->getSession();
        if (empty($session)) {
            // some default variables without session
            return xarSession::getDefaultVar($name);
        }
        if (array_key_exists($name, $session->vars)) {
            return $session->vars[$name];
        }
        if ($name == 'role_id') {
            // @todo look up userId or return xarSession::getAnonId()
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
        $session = $this->getContext()?->getSession();
        if (empty($session)) {
            return false;
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
        $session = $this->getContext()?->getSession();
        if (empty($session)) {
            return false;
        }
        if (array_key_exists($name, $session->vars)) {
            unset($session->vars[$name]);
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
        $session = $this->getContext()?->getSession();
        if (empty($session)) {
            return false;
        }
        $session->setUserId($userId);
        $session->vars['remember'] = $rememberSession;
        return true;
    }

    /**
     * Clear all the sessions in the sessions table
     * @param array<mixed> $spared a list of roles IDs whose sessions are left untouched
     * @return bool
     */
    public function clear($spared = [])
    {
        $session = $this->getContext()?->getSession();
        if (empty($session)) {
            return false;
        }
        // @todo what do we want to do here?
        return true;
    }
}
