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

/**
 * Virtual session aligned with database fields used in SessionHandler
 */
class VirtualSession
{
    public string $sessionId;
    public int $userId;
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
        return self::create($args);
    }

    /**
     * Summary of create
     * @param array<string, mixed> $data
     * @param ?string $sessionId
     * @return VirtualSession
     */
    public static function create($data, $sessionId = null)
    {
        $data['sessionId'] ??= $sessionId ?? bin2hex(random_bytes(16));
        $data['userId'] ??= 0;
        $data['ipAddress'] ??= '';
        $data['lastUsed'] ??= 0;
        $data['vars'] ??= [];
        //$session = self::__set_state($data);
        $session = new self($data['sessionId'], $data['userId'], $data['ipAddress'], $data['lastUsed'], $data['vars']);
        $session->firstUsed = $data['firstUsed'] ?? 0;
        $session->isNew = $data['isNew'] ?? ($data['lastUsed'] == $data['firstUsed'] ? true : false);
        return $session;
    }
}
