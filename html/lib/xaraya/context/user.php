<?php

/**
 * @package core\context
 * @subpackage context
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Context;

use Xaraya\Authentication\AuthToken;
use Xaraya\Authentication\RemoteUser;
use Xaraya\Authentication\SessionCookie;
use Xaraya\Services\WithServicesTrait;
use Exception;

/**
 * Get userId from user context with token or cookie
 */
class UserContext
{
    use WithServicesTrait;

    /** @var Context<string, mixed> */
    protected Context $context;

    /**
     * @param Context<string, mixed> $context
     */
    public function __construct(Context $context, $xar = null)
    {
        $this->context = $context;
        $this->setServicesClass($xar);
    }

    /**
     * Summary of getUserId - entrypoint for session in rest handler and graphql
     * @return int|null
     */
    public function getUserId()
    {
        // if we already have a session, get the userId from there - see session middleware
        $session = $this->context->getSession();
        if (!empty($session)) {
            return $session->getUserId();
        }
        // if not, check if we have an auth token or cookie - see rest handler and graphql
        $userId = $this->checkUser();
        if (!empty($userId)) {
            return $userId;
        }
        $userId = $this->checkToken();
        if (!empty($userId)) {
            return $userId;
        }
        return $this->checkCookie();
    }

    /**
     * Summary of checkUser
     * @return int|null
     */
    protected function checkUser()
    {
        try {
            $xar = $this->getServicesClass();
            RequestContext::$remoteUser = $xar->sysConfig()->getVar('Auth.RemoteUser');
        } catch (Exception) {
            return null;
        }
        $uname = RequestContext::getRemoteUser($this->context);
        if (empty($uname)) {
            return null;
        }
        $remoteUser = new RemoteUser($xar);
        $userId = $remoteUser->getUserId($uname);
        if (empty($userId)) {
            return null;
        }
        $sessionId = 'RemoteUser:' . $uname;
        $this->initSession($sessionId, $userId);
        return $userId;
    }

    /**
     * Summary of checkToken
     * @return int|null
     */
    protected function checkToken()
    {
        try {
            $xar = $this->getServicesClass();
            RequestContext::$authToken = $xar->sysConfig()->getVar('Auth.AuthToken');
        } catch (Exception) {
            return null;
        }
        $token = RequestContext::getAuthToken($this->context);
        if (empty($token)) {
            return null;
        }
        $authToken = new AuthToken($xar);
        $userId = $authToken->getUserId($token);
        if (empty($userId)) {
            return null;
        }
        $sessionId = 'AuthToken:' . $token;
        $this->initSession($sessionId, $userId);
        return $userId;
    }

    /**
     * Summary of checkCookie - using xar::session()
     * @uses xar::session()->start()
     * @uses xar::session()->getUserId()
     * @return int|null
     */
    protected function checkCookie()
    {
        $sessionId = RequestContext::getSessionCookie($this->context);
        if (empty($sessionId)) {
            return null;
        }
        $xar = $this->getServicesClass();
        // @todo replace with something that doesn't depend on PHP sessions
        $xar->session()->start();
        // @todo create virtual session for anonymous user here too?
        $xar->session()->getInstance()->setContext($this->context);
        if (!$xar->user()->isLoggedIn()) {
            return null;
        }
        return $xar->session()->getUserId();
    }

    /**
     * Summary of checkCookie2 (not used)
     * @return int|null
     */
    protected function checkCookie2()
    {
        try {
            $xar = $this->getServicesClass();
            RequestContext::$cookieName = $xar->sysConfig()->getVar('Auth.SessionCookie');
        } catch (Exception) {
            return null;
        }
        $sessionId = RequestContext::getSessionCookie($this->context);
        if (empty($sessionId)) {
            return null;
        }
        $sessionCookie = new SessionCookie($xar);
        $userId = $sessionCookie->getUserId($token);
        if (empty($userId)) {
            return null;
        }
        // @todo don't start session here if handled in SessionCookie()
        // $this->initSession($sessionId, $userId);
        return $userId;
    }

    /**
     * Summary of initSession
     * @param string $sessionId
     * @param int $userId
     * @return void
     * @uses \Xaraya\Context\SessionContext::startSession()
     */
    protected function initSession($sessionId, $userId)
    {
        $xar = $this->getServicesClass();
        if (!empty($xar->session()->getInstance())) {
            throw new Exception('Session was already initialized');
        }
        $xar->session()->setSessionClass(SessionContext::class);
        $xar->session()->start();
        $serverVars = $this->context['server'] ?? [];
        $ipAddress = $serverVars['REMOTE_ADDR'] ?? '-';
        $xar->session()->getInstance()?->startSession($this->context, $sessionId, $userId, $ipAddress);
    }
}
