<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Authentication\AuthToken;
use Xaraya\Context\Context;
use Xaraya\Context\RequestContext;
use Xaraya\Sessions\VirtualSession;

/**
 * We need to run each test in a separate process here to avoid session issues
 *
 * @runTestsInSeparateProcesses
 */
final class UserContextTest extends TestCase
{
    protected function setUp(): void
    {
        xarCache::init();
        xarDatabase::init();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_abort();
        }
    }

    public function testEmptyContext(): void
    {
        $context = new Context([
            'server' => [],
        ]);
        $expected = null;
        $userId = $context->getUserId();
        $this->assertEquals($expected, $userId);
    }

    public function testRemoteUserContext(): void
    {
        xarCoreCache::setCached('Testing:' . sys::CONFIG, 'Auth.RemoteUser', true);
        $expected = RequestContext::$remoteUser;
        xarSystemVars::set(sys::CONFIG, 'Auth.RemoteUser', $expected);
        $this->assertEquals($expected, xarSystemVars::get(sys::CONFIG, 'Auth.RemoteUser'));

        // admin user
        $expected = 6;
        $role = xarRoles::get($expected);
        $context = new Context([
            'server' => ['REMOTE_USER' => $role->getUser()],
        ]);
        $userId = $context->getUserId();
        $this->assertEquals($expected, $userId);
        $expected = VirtualSession::class;
        $this->assertEquals($expected, get_class($context->getSession()));

        xarSystemVars::set(sys::CONFIG, 'Auth.RemoteUser', null);
        xarCoreCache::delCached('Testing:' . sys::CONFIG, 'Auth.RemoteUser');
    }

    public function testAuthTokenContext(): void
    {
        xarCoreCache::setCached('Testing:' . sys::CONFIG, 'Auth.AuthToken', true);
        $expected = RequestContext::$authToken;
        xarSystemVars::set(sys::CONFIG, 'Auth.AuthToken', $expected);
        $this->assertEquals($expected, xarSystemVars::get(sys::CONFIG, 'Auth.AuthToken'));

        $expected = 123;
        $userInfo = ['userId' => $expected, 'access' => 'ignored'];
        $token = AuthToken::createToken($userInfo);
        $context = new Context([
            'server' => ['HTTP_X_AUTH_TOKEN' => $token],
        ]);
        $userId = $context->getUserId();
        $this->assertEquals($expected, $userId);
        $expected = VirtualSession::class;
        $this->assertEquals($expected, get_class($context->getSession()));

        //xarSystemVars::set(sys::CONFIG, 'Auth.AuthToken', null);
        xarCoreCache::delCached('Testing:' . sys::CONFIG, 'Auth.AuthToken');
    }

    protected function getLastSessionInfo($userId = 5)
    {
        $dbconn = xarDB::getConn();
        $prefix = xarDB::getPrefix();
        $sessionTable = $prefix . '_session_info';
        $query = "SELECT id, role_id, ip_addr, last_use, vars FROM $sessionTable WHERE role_id = ? ORDER BY last_use DESC";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->setLimit(1);
        $result = $stmt->executeQuery([$userId], xarDB::FETCHMODE_ASSOC);
        $result->first();
        $sessionInfo = $result->getRow();
        return $sessionInfo;
    }

    public function testSessionCookieContext_Anon(): void
    {
        // get last session for anonymous user
        $expected = 5;
        $sessionInfo = $this->getLastSessionInfo($expected);

        // we need to set $_COOKIE here to use the default PHP session handling
        $_COOKIE[RequestContext::$cookieName] = $sessionInfo['id'];
        $context = new Context([
            'cookie' => $_COOKIE,
        ]);
        // expecting no userId and no session in context here
        $expected = null;
        $userId = $context->getUserId();
        $this->assertEquals($expected, $userId);
        $expected = null;
        $this->assertEquals($expected, $context->getSession());

        unset($_COOKIE[RequestContext::$cookieName]);
    }

    public function testSessionCookieContext_User(): void
    {
        // get last session for admin user
        $expected = 6;
        $sessionInfo = $this->getLastSessionInfo($expected);

        // we need to set $_COOKIE here to use the default PHP session handling
        $_COOKIE[RequestContext::$cookieName] = $sessionInfo['id'];
        $context = new Context([
            'cookie' => $_COOKIE,
        ]);
        // expecting userId and session in context here
        $userId = $context->getUserId();
        $this->assertEquals($expected, $userId);
        $expected = VirtualSession::class;
        $this->assertEquals($expected, get_class($context->getSession()));

        // verify that we have the same sessionId
        $expected = $sessionInfo['id'];
        $sessionId = xarSession::getId();
        $this->assertEquals($expected, $sessionId);

        // verify that we have the expected userId
        $expected = $sessionInfo['role_id'];
        $userId = xarSession::getInstance()->getUserId();
        $this->assertEquals($expected, $userId);

        // XARSVrole_id|i:6;XARSVrand|i:1012024923;...
        $expected = $sessionInfo['vars'];
        $sessionVars = session_encode();
        $this->assertEquals($expected, $sessionVars);

        unset($_COOKIE[RequestContext::$cookieName]);
    }
}
