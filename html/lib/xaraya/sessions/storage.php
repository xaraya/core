<?php

/**
 * @package core\sessions
 * @subpackage storage
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Sessions\Storage;

use Xaraya\Sessions\VirtualSession;
use Exception;
use Xaraya\Services\xar;

/**
 * Session storage interface for virtual sessions
 */
interface SessionStorageInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config);
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
    /** @var \Connection|\PDOConnection */
    private $db;
    private string $table;
    protected $xarDb = null;         // Access database service with instance methods

    /**
     * Access database service
     */
    protected function db()
    {
        $this->xarDb ??= xar::db();
        return $this->xarDb;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private array $config)
    {
        $this->db = $this->db()->getConn();
        $this->table = $this->getTable();
    }

    private function getTable(): string
    {
        $tables = $this->db()->getTables();
        if (!isset($tables['session_info'])) {
            // Register tables this subsystem uses
            $tables = ['session_info' => $this->db()->getPrefix() . '_session_info'];
            $this->db()->importTables($tables);
        }
        return $tables['session_info'];
    }

    public function lookup(string $sessionId, string $ipAddress = ''): ?VirtualSession
    {
        // @todo do we want to lookup RemoteUser: or AuthToken: sessions in storage here?
        if (str_contains($sessionId, ':')) {
            return null;
        }
        $query = "SELECT role_id, ip_addr, last_use, vars FROM $this->table WHERE id = ?";
        $stmt = $this->db->prepareStatement($query);
        $result = $stmt->executeQuery([$sessionId], $this->db()->getFetchNum());

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
            try {
                $vars = unserialize((string) $varString);
            } catch (\Throwable $e) {
                // might be from internal 'php' session.serialize_handler
                if (ini_get('session.serialize_handler') == 'php') {
                    // ...
                    $vars = self::unserialize_php($varString);
                }
            }
            if (empty($vars)) {
                $vars = [];
            }
        }
        $session = new VirtualSession($sessionId, $userId, $ipAddress, $lastUsed, $vars);
        $session->isNew = false;
        return $session;
    }

    public function register(VirtualSession $session): void
    {
        // @todo do we want to register RemoteUser: or AuthToken: sessions in storage here?
        if (str_contains($session->sessionId, ':')) {
            return;
        }
        $query = "INSERT INTO $this->table (id, ip_addr, role_id, first_use, last_use, vars)
            VALUES (?,?,?,?,?,?)";
        $bindvars = [$session->sessionId, $session->ipAddress, $session->getUserId(), time(), time(), serialize($session->vars)];
        $stmt = $this->db->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
    }

    public function update(VirtualSession $session): void
    {
        // @todo do we want to update RemoteUser: or AuthToken: sessions in storage here?
        if (str_contains($session->sessionId, ':')) {
            return;
        }
        // @todo still not compatible with session.serialize_handler = 'php' here
        if (false && ini_get('session.serialize_handler') == 'php') {
            $data = self::serialize_php($session->vars);
        } else {
            $data = serialize($session->vars);
        }
        $query = "UPDATE $this->table
            SET role_id = ?, ip_addr = ?, vars = ?, last_use = ?
            WHERE id = ?";
        $bindvars = [$session->getUserId(), $session->ipAddress, $data, time(), $session->sessionId];
        $stmt = $this->db->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
    }

    public function delete(VirtualSession $session): void
    {
        // @todo do we want to delete RemoteUser: or AuthToken: sessions in storage here?
        if (str_contains($session->sessionId, ':')) {
            return;
        }
        $query = "DELETE FROM $this->table WHERE id = ?";
        $this->db->execute($query, [$session->sessionId]);
    }

    /**
     * Summary of unserialize_php
     * @param string $session_data
     * @throws \Exception
     * @return array<string, mixed>
     * @see https://www.php.net/manual/en/function.session-decode.php#108037
     */
    private static function unserialize_php($session_data)
    {
        $return_data = [];
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

    /**
     * Summary of serialize_php
     * @todo still not compatible with session.serialize_handler = 'php' here
     * Taken from http://www.php.net/manual/en/function.session-encode.php#76425
     * @param array<string, mixed> $array
     * @param bool $safe
     * @return string
     * @see https://stackoverflow.com/questions/15538787/safety-of-these-methods-to-encode-and-decode-php-sessions
     */
    private static function serialize_php($array, $safe = true)
    {
        // the session is passed as reference, even if you dont want it to
        if ($safe) {
            $array = unserialize(serialize($array)) ;
        }
        $raw = '' ;
        $line = 0 ;
        $keys = array_keys($array) ;
        foreach ($keys as $key) {
            $value = $array[ $key ] ;
            $line++ ;
            $raw .= $key . '|' ;
            if (is_array($value) && isset($value['huge_recursion_blocker_we_hope'])) {
                $raw .= 'R:' . $value['huge_recursion_blocker_we_hope'] . ';' ;
            } else {
                $raw .= serialize($value) ;
            }
            $array[$key] = [ 'huge_recursion_blocker_we_hope' => $line ] ;
        }
        return $raw;
    }
}
