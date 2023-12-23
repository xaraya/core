<?php
/**
 * @package core\sessions
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Sessions;

use sys;

sys::import('xaraya.sessions.interface');

/**
 * Virtual session aligned with database fields used in SessionHandler
 */
class VirtualSession
{
    public string $sessionId;
    private int $userId;
    public string $ipAddress;
    public int $firstUsed;
    public int $lastUsed;
    /** @var array<string, mixed> */
    public array $vars;
    public bool $isNew = true;

    /**
     * Summary of __construct
     * @param string $sessionId
     * @param int $userId
     * @param string $ipAddress
     * @param int $lastUsed
     * @param array<string, mixed> $vars
     */
    public function __construct(string $sessionId, int $userId = 0, string $ipAddress = '', int $lastUsed = 0, array $vars = [])
    {
        $this->sessionId = $sessionId;
        $this->setUserId($userId);
        $this->ipAddress = $ipAddress;
        $this->lastUsed = $lastUsed;
        if (empty($vars)) {
            $vars = ['rand' => rand()];
        }
        $this->vars = $vars;
    }

    /**
     * Summary of getSessionId
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Summary of setSessionId
     * @param string $sessionId
     * @return void
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Summary of getUserId
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Summary of setUserId
     * @param int $userId
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Magic method to re-create session based on result of var_export($session, true)
     * @param array<string, mixed> $args
     * @return VirtualSession
     */
    public static function __set_state($args)
    {
        // not using new static() here - see https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static
        $c = new self($args['sessionId'], $args['userId'], $args['ipAddress'], $args['lastUsed'], $args['vars']);
        $c->isNew = $args['isNew'];
        return $c;
    }
}
