<?php
/**
 * Authsystem Module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Authentication;

use Xaraya\Structures\Context;
use xarSession;
use xarUser;
use sys;

sys::import('modules.authsystem.class.authtoken');

/**
 * Get userId from user context with token or cookie
 */
class Usercontext
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
     * Summary of getUserId
     * @return int|null
     */
    public function getUserId()
    {
        $userId = $this->checkToken();
        if (!empty($userId)) {
            return $userId;
        }
        return $this->checkCookie();
    }

    /**
     * Summary of checkToken
     * @return int|null
     */
    protected function checkToken()
    {
        $token = AuthToken::getAuthToken($this->context);
        if (empty($token)) {
            return null;
        }
        $userInfo = AuthToken::getUserInfo($token);
        if (!empty($userInfo) && !empty($userInfo['userId'])) {
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
        $cookieVars = $this->context['cookie'] ?? null;
        if (empty($cookieVars) || empty($cookieVars['XARAYASID'])) {
            return null;
        }
        // @todo replace with something that doesn't depend on PHP sessions
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            xarSession::init();
        }
        //xarMLS::init();
        //xarUser::init();
        if (!xarUser::isLoggedIn()) {
            return null;
        }
        return xarSession::getVar('role_id');
    }
}
