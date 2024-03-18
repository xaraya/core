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

use Xaraya\Authentication\AuthToken;
use Xaraya\Authentication\RemoteUser;
use xarSession;
use xarUser;
use xarSystemVars;
use Exception;
use sys;

sys::import('modules.authsystem.class.authtoken');
sys::import('modules.authsystem.class.remoteuser');

/**
 * Get userId from user context with token or cookie
 */
class UserContext
{
    /** @var Context<string, mixed> */
    protected Context $context;

    /**
     * @param Context<string, mixed> $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
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
            RequestContext::$remoteUser = xarSystemVars::get(sys::CONFIG, 'Auth.RemoteUser');
        } catch (Exception) {
            return null;
        }
        $uname = RequestContext::getRemoteUser($this->context);
        if (empty($uname)) {
            return null;
        }
        $userInfo = RemoteUser::getUserInfo($uname);
        if (!empty($userInfo) && !empty($userInfo['id'])) {
            //$this->context['userInfo'] = $userInfo;
            return intval($userInfo['id']);
        }
        return null;
    }

    /**
     * Summary of checkToken
     * @return int|null
     */
    protected function checkToken()
    {
        try {
            RequestContext::$authToken = xarSystemVars::get(sys::CONFIG, 'Auth.AuthToken');
        } catch (Exception) {
            return null;
        }
        $token = RequestContext::getAuthToken($this->context);
        if (empty($token)) {
            return null;
        }
        $userInfo = AuthToken::getUserInfo($token);
        if (!empty($userInfo) && !empty($userInfo['userId'])) {
            //$this->context['userInfo'] = $userInfo;
            return intval($userInfo['userId']);
        }
        return null;
    }

    /**
     * Summary of checkCookie
     * @uses xarSession::init()
     * @uses xarUser::isLoggedIn()
     * @return int|null
     */
    protected function checkCookie()
    {
        $sessionId = RequestContext::getSessionCookie($this->context);
        if (empty($sessionId)) {
            return null;
        }
        // @todo replace with something that doesn't depend on PHP sessions
        xarSession::init();
        //xarMLS::init();
        //xarUser::init();
        if (!xarUser::isLoggedIn()) {
            return null;
        }
        return xarSession::getVar('role_id');
    }
}
