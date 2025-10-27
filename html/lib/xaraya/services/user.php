<?php

/**
 * User available via methods (WIP)
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

use xarUser;
use xarSession;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via UserTrait
 */
interface UserInterface extends ServiceInterface
{
    public const SLICE = 'user2';

    public function getVar(string $varName): mixed;
    public function setVar(string $varName, mixed $value): bool;
    public function getId(): ?int;
    public function getName(): string;
    public function getUser(): string;
    public function getEmail(): string;
    public function isLoggedIn(): bool;
    public function isDebugAdmin(): bool;
    public function isSiteAdmin(): bool;
    public function getLocale(): mixed;
    public function setLocale(string $locale): bool;
    public function getThemeName(): mixed;
    public function setThemeName(string $themeName): void;
    public function getCurrentId(): ?int;
    public function setCurrentId(int $userId): void;
}

/**
 * User available via methods
 */
trait UserTrait
{
    use ServiceTrait;

    protected ?int $currentId = null;

    /**
     * Get user variable
     */
    public function getVar(string $varName): mixed
    {
        return xarUser::getVar($varName, $this->getCurrentId());
    }

    /**
     * Set user variable
     */
    public function setVar(string $varName, mixed $value): bool
    {
        return xarUser::setVar($varName, $value, $this->getCurrentId());
    }

    /**
     * Get current userId from session (if any) or anonymous userId or null
     */
    public function getId(): ?int
    {
        // @todo see UserContext::getUserId() for userId without session
        return xarSession::getUserId();
    }

    public function getName(): string
    {
        return $this->getVar('name');
    }

    public function getUser(): string
    {
        return $this->getVar('uname');
    }

    public function getEmail(): string
    {
        return $this->getVar('email');
    }

    public function isLoggedIn(): bool
    {
        // @todo see UserContext::getUserId() for userId without session
        return xarUser::isLoggedIn($this->getContext());
    }

    public function isDebugAdmin(): bool
    {
        return xarUser::isDebugAdmin($this->getCurrentId());
    }

    public function isSiteAdmin(): bool
    {
        return xarUser::isSiteAdmin($this->getCurrentId());
    }

    public function getLocale(): mixed
    {
        return xarUser::getNavigationLocale();
    }

    public function setLocale(string $locale): bool
    {
        return xarUser::setNavigationLocale($locale);
    }

    public function getThemeName(): mixed
    {
        return xarUser::getNavigationThemeName();
    }

    public function setThemeName(string $themeName): void
    {
        xarUser::setNavigationThemeName($themeName);
    }

    /**
     * Get current userId if overridden
     */
    public function getCurrentId(): ?int
    {
        return $this->currentId ?? $this->getId();
    }

    /**
     * Override current userId when called as $this->user($userId)->...
     * @param int $userId
     * @return void
     */
    public function setCurrentId(int $userId): void
    {
        $this->currentId = $userId;
    }

    /**
     * Create a specialized version of this service for a specific user ID.
     * @param mixed ...$args
     * @return ServiceInterface
     */
    public function specialize(...$args): ServiceInterface
    {
        $clone = clone $this;
        if (isset($args[0])) {
            $clone->setCurrentId($args[0]);
        }
        return $clone;
    }
}

/**
 * Access xarUser::* User methods (getVar, setVar, ...)
 *
 * Available methods:
 * - getVar()
 * - setVar()
 * - getId()
 * - isLoggedIn()
 * - isDebugAdmin()
 * - isSiteAdmin()
 * - ...
 *
 */
class UserService implements UserInterface
{
    use UserTrait;

    // @todo remove this when all specialize() methods are implemented
    public function __clone()
    {
        $this->currentId = null;
    }
}
