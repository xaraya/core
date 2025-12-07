<?php

/**
 * Authsystem Module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Authentication;

use Xaraya\Context\Context;
use Xaraya\Context\RequestContext;
use Xaraya\Context\UserContext;
//use Xaraya\Sessions\Storage\SessionCacheStorage;
//use Xaraya\Sessions\Storage\SessionStorageInterface;

/**
 * Session Cookie Authentication (not used)
 */
class SessionCookie extends CacheStorage
{
    public static string $cookieName = 'XARAYASID';
    public static string $cacheType = 'session';
    public static string $fieldName = 'id';
    ///** @var class-string<SessionStorageInterface> */
    //public static $storageClass = SessionCacheStorage::class;

    /**
     * Summary of init
     * @param array<string, mixed> $config
     * @return void
     */
    public static function init(array $config = [])
    {
        // @todo Change the cookie name for the session cookie if needed
        // RequestContext::$cookieName = 'XARAYASID';
    }

    /**
     * Summary of getSessionCookie
     * @param Context<string, mixed> $context
     * @return string
     */
    public static function getSessionCookie($context): string
    {
        return RequestContext::getSessionCookie($context);
    }

    /**
     * Summary of getUserId
     * @param string $sessionId
     * @return int|null
     * @see UserContext::checkCookie()
     */
    public function getUserId($sessionId)
    {
        $xar = $this->getServicesClass();
        // @todo replace with something that doesn't depend on PHP sessions
        $xar->session()->start();
        // @todo create virtual session for anonymous user here too?
        $xar->session()->getInstance()->setContext($xar->getContext());
        if (!$xar->user()->isLoggedIn()) {
            return null;
        }
        return $xar->session()->getUserId();
    }
}
