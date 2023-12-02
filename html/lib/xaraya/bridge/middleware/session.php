<?php
/**
 * Sessions for PSR-7 and PSR-15 compatible middleware controllers (not functional)
 *
 * In general, single-user sessions, authentication and authkey confirmation are ok,
 * but multi-user sessions clash with use of superglobals in several core classes...
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use xarSession;
use xarConfigVars;
use xarServer;
use xarDB;
use xarEvents;
use ResultSet;

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

interface SessionStorageInterface
{
    public function lookup(string $sessionId, string $ipAddress = ''): ?VirtualSession;
    public function register(VirtualSession $session): void;
    public function update(VirtualSession $session): void;
    public function delete(VirtualSession $session): void;
}

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
        $tables = xarDB::getTables();
        $this->table = $tables['session_info'];
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

class SessionMiddleware implements MiddlewareInterface
{
    private string $cookieName;
    private int $anonId;
    private int $length = 32;
    /** @var array<string, mixed> */
    private array $config;
    /** @var SessionStorageInterface */
    private $storage;

    public function __construct()
    {
        $this->cookieName = xarSession::COOKIE;
        $this->anonId = (int) xarConfigVars::get(null, 'Site.User.AnonymousUID', 5);
        $this->config = xarSession::getConfig();
        //$this->storage = new SessionDatabaseStorage($this->config);
        $this->storage = new SessionCacheStorage($this->config);
    }

    /**
     * Register callback functions for UserLogin and UserLogout events - to update userId in request
     */
    public function registerCallbackEvents(ServerRequestInterface &$request): void
    {
        $login = function ($info) use (&$request) {
            echo "Event: " . $info['event'] . "\n";
            $request = $request->withAttribute('userId', $info['args']);
        };
        xarEvents::registerCallback('UserLogin', $login);
        $logout = function ($info) use (&$request) {
            echo "Event: " . $info['event'] . "\n";
            $request = $request->withAttribute('userId', 0);
        };
        xarEvents::registerCallback('UserLogout', $logout);
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
            $_SESSION[xarSession::PREFIX . 'role_id'] = $session->getUserId();
            $request = $request->withAttribute('userId', $session->getUserId());
            echo "Token: " . var_export($session, true) . "\n";
        } elseif (array_key_exists($this->cookieName, $cookies)) {
            $sessionId = $cookies[$this->cookieName];
            $session = $this->getSession($sessionId);
            $_SESSION[xarSession::PREFIX . 'role_id'] = $session->getUserId();
            if (!empty($session->vars)) {
                // @checkme - see isAuthKey below
                foreach ($session->vars as $key => $value) {
                    $_SESSION[xarSession::PREFIX . $key] = $value;
                }
            }
            $request = $request->withAttribute('userId', $session->getUserId());
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
        if (strpos($request->getRequestTarget(), '/authsystem/') !== false) {
            $this->registerCallbackEvents($request);
            $isAuthSystem = true;
        }
        $isAuthToken = false;
        if (strpos($request->getRequestTarget(), '/restapi/token') !== false && $request->getMethod() == 'POST') {
            $isAuthToken = true;
        }
        $isAuthKey = false;
        if (!empty($sessionId) && $request->getMethod() == 'POST') {
            $input = $request->getParsedBody();
            if (!empty($input['authid']) && empty($input['preview'])) {
                //$key = 'rand';
                //$_SESSION[xarSession::PREFIX . $key] = $session->vars[$key];
                $_POST['authid'] = $input['authid'];
                $isAuthKey = true;
            }
        }
        // @checkme signature mismatch for process() with ReactPHP
        if ($next instanceof RequestHandlerInterface) {
            $response = $next->handle($request);
        } else {
            $response = $next($request);
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
            echo "Session: " . var_export($_SESSION, true) . "\n";
            session_write_close();
            $userId = 0;
            foreach (array_keys($_SESSION) as $key) {
                if (strpos($key, xarSESSION::PREFIX) === 0) {
                    //$session->vars[$key] = $_SESSION[$key];
                    // @checkme successful login without a previous sessionId?
                    //if ($isLogin && $key === xarSession::PREFIX . 'role_id') {
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
        } elseif (isset($_SESSION[xarSession::PREFIX . 'role_id'])) {
            unset($_SESSION[xarSession::PREFIX . 'role_id']);
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

class SingleSessionMiddleware extends SessionMiddleware
{
    // clarify we only support a single session here
}
