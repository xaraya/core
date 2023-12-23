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
use xarDB;

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
