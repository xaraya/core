<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Authentication\AuthToken;
use Xaraya\Authentication\SessionCookie;
use Xaraya\Context\Context;
use Xaraya\Context\RequestContext;
use Xaraya\Context\UserContext;
use Xaraya\Sessions\VirtualSession;
use Xaraya\Services\xar;

/**
 * We need to run each test in a separate process here to avoid session issues
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class UserContextTest extends TestCase
{
    protected $xarServices;

    protected function setUp(): void
    {
        xar::cache()->init();
        xar::db()->init();

        // Set context for core services here first
        $context = new Context();
        $this->xarServices = xar::setServicesContext($context);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_abort();
        }
    }

    protected function getServicesClass()
    {
        return $this->xarServices;
    }

    public function testEmptyContext(): void
    {
        $context = new Context([
            'server' => [],
        ]);
        $xar = $this->getServicesClass();
        $expected = null;
        $userId = $context->getUserId($xar);
        $this->assertEquals($expected, $userId);

        $expected = null;
        $this->assertEquals($expected, $context->getSession());
    }

    public function testRemoteUserContext(): void
    {
        xar::mem()->set('Testing:' . sys::CONFIG, 'Auth.RemoteUser', true);
        $expected = RequestContext::$remoteUser;
        xar::sysConfig()->setVar('Auth.RemoteUser', $expected);
        $this->assertEquals($expected, xar::sysConfig()->getVar('Auth.RemoteUser'));

        // admin user
        $expected = 6;
        $role = xarRoles::get($expected);
        $context = new Context([
            'server' => ['REMOTE_USER' => $role->getUser()],
        ]);
        $xar = $this->getServicesClass();
        $userId = $context->getUserId($xar);
        $this->assertEquals($expected, $userId);
        $expected = VirtualSession::class;
        $this->assertEquals($expected, $context->getSession()::class);

        xar::sysConfig()->setVar('Auth.RemoteUser', null);
        xar::mem()->del('Testing:' . sys::CONFIG, 'Auth.RemoteUser');
    }

    public function testAuthTokenContext(): void
    {
        xar::mem()->set('Testing:' . sys::CONFIG, 'Auth.AuthToken', true);
        $expected = RequestContext::$authToken;
        xar::sysConfig()->setVar('Auth.AuthToken', $expected);
        $this->assertEquals($expected, xar::sysConfig()->getVar('Auth.AuthToken'));

        $expected = 123;
        $userInfo = ['userId' => $expected, 'access' => 'ignored'];
        $xar = $this->getServicesClass();
        $authToken = new AuthToken($xar);
        $token = $authToken->createItem($userInfo);
        $context = new Context([
            'server' => ['HTTP_X_AUTH_TOKEN' => $token],
        ]);
        $userId = $context->getUserId($xar);
        $this->assertEquals($expected, $userId);
        $expected = VirtualSession::class;
        $this->assertEquals($expected, $context->getSession()::class);

        xar::sysConfig()->setVar('Auth.AuthToken', null);
        xar::mem()->del('Testing:' . sys::CONFIG, 'Auth.AuthToken');
    }

    protected function getLastSessionInfo($userId = 5)
    {
        $dbconn = xarDB::getConn();
        $prefix = xarDB::getPrefix();
        $sessionTable = $prefix . '_session_info';
        $query = "SELECT id, role_id, ip_addr, last_use, vars FROM $sessionTable WHERE role_id = ? AND id NOT LIKE '%:%' ORDER BY last_use DESC";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->setLimit(1);
        $result = $stmt->executeQuery([$userId], xarDB::getFetchAssoc());
        $result->first();
        $sessionInfo = $result->getRow();
        return $sessionInfo;
    }

    public function testSessionCookieContext_Anon(): void
    {
        // get last session for anonymous user
        $expected = 5;
        $sessionInfo = $this->getLastSessionInfo($expected);
        $this->assertNotEmpty($sessionInfo);

        // we need to set $_COOKIE here to use the default PHP session handling
        $_COOKIE[RequestContext::$cookieName] = $sessionInfo['id'];
        $context = new Context([
            'cookie' => $_COOKIE,
        ]);
        $xar = $this->getServicesClass();
        // expecting no userId in context here
        $expected = null;
        $userId = $context->getUserId($xar);
        $this->assertEquals($expected, $userId);

        // @todo expecting session in context here
        $expected = VirtualSession::class;
        $this->assertEquals($expected, $context->getSession()::class);

        // verify that we have the same sessionId
        $expected = $sessionInfo['id'];
        $sessionId = xar::session()->getId();
        $this->assertEquals($expected, $sessionId);

        unset($_COOKIE[RequestContext::$cookieName]);
    }

    public function testSessionCookieContext_User(): void
    {
        // get last session for admin user
        $expected = 6;
        $sessionInfo = $this->getLastSessionInfo($expected);
        $this->assertNotEmpty($sessionInfo);

        // we need to set $_COOKIE here to use the default PHP session handling
        $_COOKIE[RequestContext::$cookieName] = $sessionInfo['id'];
        $context = new Context([
            'cookie' => $_COOKIE,
        ]);
        $xar = $this->getServicesClass();
        // expecting userId and session in context here
        $userId = $context->getUserId($xar);
        $this->assertEquals($expected, $userId);
        $expected = VirtualSession::class;
        $this->assertEquals($expected, $context->getSession()::class);

        // verify that we have the same sessionId
        $expected = $sessionInfo['id'];
        $sessionId = xar::session()->getId();
        $this->assertEquals($expected, $sessionId);

        // verify that we have the expected userId
        $expected = $sessionInfo['role_id'];
        $userId = xar::session()->getInstance()->getUserId();
        $this->assertEquals($expected, $userId);

        // XARSVrole_id|i:6;XARSVrand|i:1012024923;...
        $expected = $sessionInfo['vars'];
        $sessionVars = session_encode();
        $this->assertEquals($expected, $sessionVars);

        unset($_COOKIE[RequestContext::$cookieName]);
    }

    public function testSessionCookieContext_User_Cached(): void
    {
        // get last session for admin user
        $expected = 6;
        $sessionInfo = $this->getLastSessionInfo($expected);
        $this->assertNotEmpty($sessionInfo);

        // we need to set $_COOKIE here to use the default PHP session handling
        $_COOKIE[RequestContext::$cookieName] = $sessionInfo['id'];
        $context = new Context([
            'cookie' => $_COOKIE,
        ]);
        $xar = $this->getServicesClass();

        // verify that checkCookie2 returns the same result as above
        $userContext = new UserContext($context, $xar);
        $userId = $userContext->checkCookie2();

        unset($_COOKIE[RequestContext::$cookieName]);
        $this->assertEquals($expected, $userId);

        // save VirtualSession() as array in cache storage
        $session = $xar->getContext()->getSession();
        //var_dump(serialize($session));
        $sessionCookie = new SessionCookie($xar);
        $data = $sessionCookie->fromSession($session);

        $expected = $sessionInfo['id'];
        $sessionId = $sessionCookie->createItem($data, $expected);
        $this->assertEquals($expected, $sessionId);

        // get session data back from cache storage
        $result = $sessionCookie->getUserInfo($sessionId);
        // check that lastUsed was updated when saved
        $this->assertGreaterThan($data['lastUsed'], $result['lastUsed']);
        $result['lastUsed'] = $data['lastUsed'];
        // compare values for all expected keys
        $expected = array_flip(array_keys($data));
        $this->assertEquals($data, array_intersect_key($result, $expected));

        // re-create equivalent VirtualSession() based on data - not same but equal
        $newSession = $sessionCookie->makeSession($result);
        $this->assertNotSame($session, $newSession);
        $this->assertEquals($session, $newSession);
    }
}
