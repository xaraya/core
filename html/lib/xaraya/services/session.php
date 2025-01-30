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
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarSession;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via SessionTrait
 */
interface SessionInterface extends ServiceInterface
{
    public function getVar(string $varName): mixed;
    public function setVar(string $varName, mixed $value): bool;
    public function delVar(string $varName): mixed;
    public function getUserId(): int|null;
    public function getAnonId(): int|null;
}

/**
 * Session available via methods
 */
trait SessionTrait
{
    use ServiceTrait;

    /**
     * Get session variable
     */
    public function getVar(string $varName): mixed
    {
        return xarSession::getVar($varName);
    }

    /**
     * Set session variable
     */
    public function setVar(string $varName, mixed $value): bool
    {
        return xarSession::setVar($varName, $value);
    }

    /**
     * Delete session variable
     */
    public function delVar(string $varName): bool
    {
        return xarSession::delVar($varName);
    }

    /**
     * Get current userId from session (if any) or anonymous userId or null
     */
    public function getUserId(): int|null
    {
        // @todo see UserContext::getUserId() for userId without session
        return xarSession::getUserId();
    }

    /**
     * Get the anonymous userId or null if no session has been initialized
     */
    public function getAnonId(): int|null
    {
        return xarSession::getAnonId();
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
