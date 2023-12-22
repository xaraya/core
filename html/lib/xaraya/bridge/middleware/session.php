<?php
/**
 * Sessions for PSR-7 and PSR-15 compatible middleware controllers (not functional)
 *
 * In general, single-user sessions, authentication and authkey confirmation are ok,
 * but multi-user sessions clash with use of superglobals in several core classes...
 *
 * @package core\bridge
 * @subpackage middleware
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xaraya\Context\ContextFactory;
use Xaraya\Context\Context;
use xarSessionHandler;
use xarSession;
use xarConfigVars;
use xarServer;
use xarDB;
use xarEvents;

/**
 * Virtual session aligned with database fields used in xarSessionHandler
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

/**
 * Session storage interface for virtual sessions
 */
interface SessionStorageInterface
{
    public function lookup(string $sessionId, string $ipAddress = ''): ?VirtualSession;
    public function register(VirtualSession $session): void;
    public function update(VirtualSession $session): void;
    public function delete(VirtualSession $session): void;
}

/**
 * Session storage in cache for virtual sessions
 */
class SessionCacheStorage implements SessionStorageInterface
{
    /** @var array<string, VirtualSession> */
    private $sessions = [];
    private int $limit = 10000;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private array $config) {}

    public function lookup(string $sessionId, string $ipAddress = ''): ?VirtualSession
    {
        if (!array_key_exists($sessionId, $this->sessions)) {
            return null;
        }
        $session = $this->sessions[$sessionId];
        // Already have this session
        if ($session->lastUsed < time() - intval($this->config['inactivityTimeout']) * 60) {
            // @todo
        }
        if ($session->ipAddress != $ipAddress) {
            // ignore
        }
        $session->isNew = false;
        return $session;
    }

    public function register(VirtualSession $session): void
    {
        if (count($this->sessions) > $this->limit * 0.95) {
            // @todo garbage collection
        }
        $session->firstUsed = time();
        $session->lastUsed = time();
        $this->sessions[$session->sessionId] = $session;
    }

    public function update(VirtualSession $session): void
    {
        $session->lastUsed = time();
        $this->sessions[$session->sessionId] = $session;
    }

    public function delete(VirtualSession $session): void
    {
        unset($this->sessions[$session->sessionId]);
    }
}

/**
 * Session storage in database for virtual sessions
 */
class SessionDatabaseStorage implements SessionStorageInterface
{
    /** @var \Connection|\xarPDO */
    private $db;
    private string $table;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private array $config)
    {
        $this->db = xarDB::getConn();
        $this->table = $this->getTable();
    }

    private function getTable()
    {
        $tables = xarDB::getTables();
        if (!isset($tables['session_info'])) {
            // Register tables this subsystem uses
            $tables = ['session_info' => xarDB::getPrefix() . '_session_info'];
            xarDB::importTables($tables);
        }
        return $tables['session_info'];
    }

    public function lookup(string $sessionId, string $ipAddress = ''): ?VirtualSession
    {
        $query = "SELECT role_id, ip_addr, last_use, vars FROM $this->table WHERE id = ?";
        $stmt = $this->db->prepareStatement($query);
        $result = $stmt->executeQuery([$sessionId], xarDB::FETCHMODE_NUM);

        if (!$result->first()) {
            return null;
        }
        // Already have this session
        [$userId, $lastAddress, $lastUsed, $varString] = $result->getRow();
        if ($lastUsed < time() - intval($this->config['inactivityTimeout']) * 60) {
            // @todo
        }
        if ($lastAddress != $ipAddress) {
            // ignore
        }
        $vars = [];
        if (!empty($varString)) {
            $vars = unserialize((string) $varString);
        }
        $session = new VirtualSession($sessionId, $userId, $ipAddress, $lastUsed, $vars);
        $session->isNew = false;
        return $session;
    }

    public function register(VirtualSession $session): void
    {
        $query = "INSERT INTO $this->table (id, ip_addr, role_id, first_use, last_use, vars)
            VALUES (?,?,?,?,?,?)";
        $bindvars = [$session->sessionId, $session->ipAddress, $session->getUserId(), time(), time(), serialize($session->vars)];
        $stmt = $this->db->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
    }

    public function update(VirtualSession $session): void
    {
        $query = "UPDATE $this->table
            SET role_id = ?, ip_addr = ?, vars = ?, last_use = ?
            WHERE id = ?";
        $bindvars = [$session->getUserId(), $session->ipAddress, serialize($session->vars), time(), $session->sessionId];
        $stmt = $this->db->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
    }

    public function delete(VirtualSession $session): void
    {
        $query = "DELETE FROM $this->table WHERE id = ?";
        $this->db->execute($query, [$session->sessionId]);
    }
}

/**
 * Sessions for PSR-7 and PSR-15 compatible middleware controllers (not functional)
 *
 * In general, single-user sessions, authentication and authkey confirmation are ok,
 * but multi-user sessions clash with use of superglobals in several core classes...
 */
class SessionMiddleware implements MiddlewareInterface
{
    private string $cookieName;
    private int $anonId;
    private int $length = 32;
    /** @var array<string, mixed> */
    private array $config;
    /** @var SessionStorageInterface */
    private $storage;
    /** @var array<string, ServerRequestInterface> */
    private $pending = [];

    public function __construct()
    {
        $this->cookieName = xarSessionHandler::COOKIE;
        $this->anonId = intval(xarConfigVars::get(null, 'Site.User.AnonymousUID', 5));
        $this->config = xarSession::getConfig();
        //$this->storage = new SessionDatabaseStorage($this->config);
        $this->storage = new SessionCacheStorage($this->config);
        // register callback functions for UserLogin and UserLogout events - to update userId in request
        $this->registerCallbackEvents();
    }

    /**
     * Register callback functions for UserLogin and UserLogout events - to update userId in request
     * Note: as alternative we can specify an 'EventCallback' in the $request which is passed to $context
     */
    public function registerCallbackEvents(): void
    {
        xarEvents::registerCallback('UserLogin', [$this, 'callbackUserLogin']);
        xarEvents::registerCallback('UserLogout', [$this, 'callbackUserLogout']);
    }

    /**
     * Specify an 'EventCallback' in the $request which is passed to $context
     * @return void
     */
    public function addEventCallbackToRequest(ServerRequestInterface &$request)
    {
        $callbackList = $request->getAttribute('EventCallback');
        $callbackList ??= [];
        $callbackList['UserLogin'] ??= [];
        $callbackList['UserLogout'] ??= [];
        array_push($callbackList['UserLogin'], [$this, 'callbackUserLogin']);
        array_push($callbackList['UserLogout'], [$this, 'callbackUserLogout']);
        $request = $request->withAttribute('EventCallback', $callbackList);
    }

    /**
     * Add request for callback in UserLogin and UserLogout events - to update userId in request
     */
    public function addCallbackRequest(ServerRequestInterface &$request, string $requestId): void
    {
        //$this->addEventCallbackToRequest($request);
        $this->pending[$requestId] = &$request;
    }

    /**
     * Remove request for callback in UserLogin and UserLogout events - to update userId in request
     */
    public function removeCallbackRequest(string $requestId): void
    {
        unset($this->pending[$requestId]);
    }

    /**
     * Callback function for UserLogin events - to update userId in pending request(s)
     * @param array<string, mixed> $info
     * @param ?Context<string, mixed> $context
     */
    public function callbackUserLogin($info, $context = null): void
    {
        if (empty($context)) {
            echo "No context given for login\n";
            return;
        }
        $requestId = $context->getRequestId() ?? '';
        if (empty($requestId) || empty($this->pending[$requestId])) {
            echo "Invalid requestId given for login\n";
            return;
        }
        $request = $this->pending[$requestId];
        echo "Event: " . $info['event'] . " for request ($requestId) " . $request->getUri()->getPath() . "\n";
        $this->pending[$requestId] = $request->withAttribute('userId', $info['args']);
        echo "Context: " . var_export($context, true) . "\n";
    }

    /**
     * Callback function for UserLogout events - to update userId in pending request(s)
     * @param array<string, mixed> $info
     * @param ?Context<string, mixed> $context
     */
    public function callbackUserLogout($info, $context = null): void
    {
        if (empty($context)) {
            echo "No context given for logout\n";
            return;
        }
        $requestId = $context->getRequestId() ?? '';
        if (empty($requestId) || empty($this->pending[$requestId])) {
            echo "Invalid requestId given for logout\n";
            return;
        }
        $request = $this->pending[$requestId];
        echo "Event: " . $info['event'] . " for request ($requestId) " . $request->getUri()->getPath() . "\n";
        $this->pending[$requestId] = $request->withAttribute('userId', 0);
    }

    /**
     * Process the server request - this assumes request attributes have been set in earlier middleware, e.g. router
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface|callable $next): ResponseInterface
    {
        $token = null;
        if ($request->hasHeader('X-Auth-Token')) {
            $token = $request->getHeaderLine('X-Auth-Token');
            echo "Token: " . var_export($token, true) . "\n";
        }
        $cookies = $request->getCookieParams();
        echo "Cookies: " . var_export($cookies, true) . "\n";
        $sessionId = null;
        if (!empty($token)) {
            $sessionId = $token;
            $session = $this->getSession($sessionId);
            $_SESSION[xarSessionHandler::PREFIX . 'role_id'] = $session->getUserId();
            $request = $request->withAttribute('userId', $session->getUserId());
            $request = $request->withAttribute('session', $session);
            echo "Token: " . var_export($session, true) . "\n";
        } elseif (array_key_exists($this->cookieName, $cookies)) {
            $sessionId = $cookies[$this->cookieName];
            $session = $this->getSession($sessionId);
            $_SESSION[xarSessionHandler::PREFIX . 'role_id'] = $session->getUserId();
            if (!empty($session->vars)) {
                // @checkme - see isAuthKey below
                foreach ($session->vars as $key => $value) {
                    $_SESSION[xarSessionHandler::PREFIX . $key] = $value;
                }
            }
            $request = $request->withAttribute('userId', $session->getUserId());
            $request = $request->withAttribute('session', $session);
            echo "Session: " . var_export($session, true) . "\n";
        } else {
            $request = $request->withAttribute('userId', 0);
            $session = null;
        }
        $isLogin = false;
        if (strpos($request->getRequestTarget(), '/authsystem/login') !== false) {
            $isLogin = true;
        }
        $isAuthSystem = false;
        $requestId = null;
        if (strpos($request->getRequestTarget(), '/authsystem/') !== false) {
            $requestId = ContextFactory::makeRequestId($request);
            echo "Adding callback request ($requestId) " . $request->getRequestTarget() . "\n";
            $this->addCallbackRequest($request, $requestId);
            $isAuthSystem = true;
        }
        $isAuthToken = false;
        if (strpos($request->getRequestTarget(), '/restapi/token') !== false && $request->getMethod() == 'POST') {
            $isAuthToken = true;
        }
        $isAuthKey = false;
        if (!empty($sessionId)) {
            $request = $request->withAttribute('sessionId', $sessionId);
            if ($request->getMethod() == 'POST') {
                $input = $request->getParsedBody();
                if (!empty($input['authid']) && empty($input['preview'])) {
                    $request = $request->withAttribute('authId', $input['authid']);
                    //$key = 'rand';
                    //$_SESSION[xarSessionHandler::PREFIX . $key] = $session->vars[$key];
                    $_POST['authid'] = $input['authid'];
                    $isAuthKey = true;
                }
            }
        }
        // @checkme signature mismatch for process() with ReactPHP
        if ($next instanceof RequestHandlerInterface) {
            $response = $next->handle($request);
        } else {
            $response = $next($request);
        }
        if (!empty($requestId)) {
            // request has changed due to redirect in the meantime, so spl_object_id($request) will not match
            echo "Removing callback request ($requestId) " . $request->getRequestTarget() . "\n";
            $this->removeCallbackRequest($requestId);
        }
        $sendCookie = false;
        if ($isAuthSystem && $request->getAttribute('userId') !== null) {
            $userId = $request->getAttribute('userId');
            if (!isset($sessionId)) {
                $sessionId = bin2hex(random_bytes($this->length));
                $session = new VirtualSession($sessionId, $userId);
                $this->storage->register($session);
                $sendCookie = true;
            } elseif (isset($session) && $userId !== $session->getUserId()) {
                $session->setUserId($userId);
                $this->storage->update($session);
                $sendCookie = true;
            }
        } elseif ($isAuthToken && $response->getStatusCode() === 200) {
            $body = (string) $response->getBody();
            $info = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $userId = $info['role_id'];
            $sessionId = $info['access_token'];
            //echo "AuthToken: userId=$userId - sessionId=$sessionId\n";
            $session = new VirtualSession($sessionId, $userId);
            $session->vars['expiration'] = $info['expiration'];
            $this->storage->register($session);
            $sendCookie = false;
        } elseif ($isAuthKey && isset($session)) {
            $session->vars['rand'] = rand();
            $this->storage->update($session);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo "\$_SESSION: " . var_export($_SESSION, true) . "\n";
            session_write_close();
            $userId = 0;
            foreach (array_keys($_SESSION) as $key) {
                if (strpos($key, xarSessionHandler::PREFIX) === 0) {
                    //$session->vars[$key] = $_SESSION[$key];
                    // @checkme successful login without a previous sessionId?
                    //if ($isLogin && $key === xarSessionHandler::PREFIX . 'role_id') {
                    //    $userId = $_SESSION[$key];
                    //}
                    unset($_SESSION[$key]);
                }
            }
            if (empty($sessionId)) {
                $sessionId = bin2hex(random_bytes($this->length));
                $session = new VirtualSession($sessionId, $userId);
                $this->storage->register($session);
                $sendCookie = true;
            } elseif ($isLogin && isset($session) && !empty($userId) && $userId !== $this->anonId) {
                $session->setUserId($userId);
                $this->storage->update($session);
                $sendCookie = true;
            }
        } elseif (isset($_SESSION[xarSessionHandler::PREFIX . 'role_id'])) {
            unset($_SESSION[xarSessionHandler::PREFIX . 'role_id']);
        }
        if ($sendCookie && !empty($sessionId)) {
            $cookieString = $this->cookieName . '=' . $sessionId;
            $cookieString .= '; expires=' . gmdate('D, d M Y H:i:s T', intval($this->config['duration']) * 86400 + time());
            $basePath = $this->config['cookiePath'] ?: xarServer::getBaseURI();
            if (!empty($basePath)) {
                $cookieString .= '; path=' . $basePath;
            }
            //$domain = $this->config['cookieDomain'] ?: xarServer::getHost();
            //if (!empty($domain)) {
            //    $cookieString .= '; domain=' . $domain;
            //}
            $cookieString .= '; secure';
            //$cookieString .= '; httponly';
            //$cookieString .= '; samesite=strict';
            echo "Cookie String: " . $cookieString . "\n";
            $response = $response->withHeader('Set-Cookie', $cookieString);
        }
        return $response;
    }

    /**
     * Summary of __invoke - @checkme signature mismatch for process() with ReactPHP
     * @param ServerRequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        return $this->process($request, $next);
    }

    private function getSession(string $sessionId, string $ipAddress = ''): VirtualSession
    {
        $session = $this->storage->lookup($sessionId, $ipAddress);

        if (!isset($session)) {
            $userId = 0;
            $session = new VirtualSession($sessionId, $userId, $ipAddress, time(), []);
            // @todo only register when we actually have a userId in update
            $this->storage->register($session);
            $session->isNew = true;
        }
        return $session;
    }
}

/**
 * Sessions for PSR-7 and PSR-15 compatible middleware controllers (not functional)
 *
 * In general, single-user sessions, authentication and authkey confirmation are ok,
 * but multi-user sessions clash with use of superglobals in several core classes...
 */
class SingleSessionMiddleware extends SessionMiddleware
{
    // clarify we only support a single session here
}
