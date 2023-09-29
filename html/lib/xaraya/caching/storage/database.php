<?php
/**
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Cache data in the database using the xar_cache_data table
 */

class xarCache_Database_Storage extends xarCache_Storage implements ixarCache_Storage
{
    public string $table = '';
    public ?string $lastkey = null;
    public ?int $lastid = null;
    public mixed $value = null;
    private ?object $dbconn = null;
    /** @var array<string, mixed> */
    private $lastinfo = null;

    public function __construct(array $args = [])
    {
        parent::__construct($args);
        $this->storage = 'database';
    }

    /**
     * Summary of getTable
     * @return string
     */
    public function getTable()
    {
        if (!empty($this->table)) {
            return $this->table;
        } elseif (class_exists('xarDB')) {
            $this->dbconn = xarDB::getConn();
            $this->table = xarDB::GetPrefix() . '_cache_data';
            return $this->table;
        } else {
            // can't use this storage until the core is loaded !
            return '';
        }
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        static $stmt = null;

        if (empty($expire)) {
            $expire = $this->expire;
        }
        if ($key == $this->lastkey && isset($this->value)) {
            return true;
        }
        $table = $this->getTable();
        if (empty($table)) {
            return false;
        }

        // we actually retrieve the value here too
        $query = "SELECT id, time, size, cache_check, data
                  FROM $table
                  WHERE type = ? AND cache_key = ? AND code = ?";
        $bindvars = [$this->type, $key, $this->code];
        // Prepare it once.
        if (!isset($stmt)) {
            $stmt = $this->dbconn->prepareStatement($query);
        }
        $result = $stmt->executeQuery($bindvars);

        $this->lastkey = $key;

        if (!$result->first()) {
            $result->close();
            $this->lastid = null;
            $this->value = null;
            if ($log) {
                $this->logStatus('MISS', $key);
            }
            return false;
        }
        [$id, $time, $size, $check, $data] = $result->fields;
        $result->close();

        // TODO: process $size and $check if compressed ?

        $this->lastid = $id;
        $this->lastinfo = ['key'   => $key,
                           'code'  => $this->code,
                           'time'  => $time,
                           'size'  => $size,
                           'check' => $check];
        if (!empty($expire) && $time < time() - $expire) {
            $this->value = null;
            if ($log) {
                $this->logStatus('MISS', $key);
            }
            return false;
        } else {
            $this->value = $data;
            $this->modtime = $time;
            if ($log) {
                $this->logStatus('HIT', $key);
            }
            return true;
        }
    }

    public function getCached($key = '', $output = 0, $expire = 0)
    {
        static $stmt;

        if (empty($expire)) {
            $expire = $this->expire;
        }
        if ($key == $this->lastkey && isset($this->value)) {
            $this->lastkey = null;
            if ($output) {
                // output the value directly to the browser
                echo $this->value;
                return true;
            } else {
                return $this->value;
            }
        }
        $table = $this->getTable();
        if (empty($table)) {
            return;
        }

        $query = "SELECT id, time, size, cache_check, data
                  FROM $table
                  WHERE type = ? AND cache_key = ? AND code = ?";
        $bindvars = [$this->type, $key, $this->code];
        // Prepare it once
        if (!isset($stmt)) {
            $stmt = $this->dbconn->prepareStatement($query);
        }
        $result = $stmt->executeQuery($bindvars);

        $this->lastkey = $key;

        if (!$result->first()) {
            $result->close();
            $this->lastid = null;
            $this->value = null;
            return;
        }
        [$id, $time, $size, $check, $data] = $result->fields;
        $result->close();

        // TODO: process $size and $check if compressed ?

        $this->lastid = $id;
        if (!empty($expire) && $time < time() - $expire + 10) { // take some margin here
            return;
        } elseif ($output) {
            // output the value directly to the browser
            echo $data;
            return true;
        } else {
            return $data;
        }
    }

    public function setCached($key = '', $value = '', $expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $time = time();
        $size = strlen($value);
        if ($this->compressed) {
            $check = crc32($value);
        } else {
            $check = 0;
        }

        $table = $this->getTable();
        if (empty($table)) {
            return;
        }

        // TODO: is a transaction warranted here?
        // Since we catch the exception if someone beat us to it, a transaction could
        // cause a deadlock here?
        if ($key == $this->lastkey && !empty($this->lastid)) {
            $query = "UPDATE $table
                         SET time = ?,
                             size = ?,
                             cache_check = ?,
                             data = ?
                       WHERE id = ?";
            $bindvars = [$time, $size, $check, $value, (int) $this->lastid];
            $stmt = $this->dbconn->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
        } else {
            try {
                $query = "INSERT INTO $table (type, cache_key, code, time, size, cache_check, data)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
                $bindvars = [$this->type, $key, $this->code, $time, $size, $check, $value];
                $stmt = $this->dbconn->prepareStatement($query);
                $stmt->executeUpdate($bindvars);
            } catch (SQLException $e) {
                // someone else beat us to it - ignore error
            }
        }
        $this->lastkey = null;
    }

    public function delCached($key = '')
    {
        $table = $this->getTable();
        if (empty($table)) {
            return;
        }

        if ($key == $this->lastkey && !empty($this->lastid)) {
            $query = "DELETE FROM $table    WHERE id = ?";
            $bindvars = [(int) $this->lastid];
        } else {
            $query = "DELETE FROM $table
                            WHERE type = ? AND cache_key = ? AND code = ?";
            $bindvars = [$this->type, $key, $this->code];
        }
        $stmt = $this->dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
        $this->lastkey = null;
    }

    /**
     * Get detailed information about the cache key (not supported by all storage)
     */
    public function keyInfo($key = '')
    {
        if ($key == $this->lastkey && !empty($this->lastinfo)) {
            return $this->lastinfo;
        }
        return ['key'   => $key,
                'code'  => $this->code,
                'time'  => time(),
                'size'  => 0,
                'check' => ''];
    }

    public function flushCached($key = '')
    {
        $table = $this->getTable();
        if (empty($table)) {
            return;
        }

        if (empty($key)) {
            $query = "DELETE FROM $table WHERE type = ?";
            $bindvars = [$this->type];
        } else {
            $key = '%'.$key.'%';
            $query = "DELETE FROM $table  WHERE type = ? AND cache_key LIKE ?";
            $bindvars = [$this->type,$key];
        }
        $stmt = $this->dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
        $this->lastkey = null;
    }

    public function doGarbageCollection($expire = 0)
    {
        $table = $this->getTable();
        if (empty($table)) {
            return;
        }

        $time = time() - ($expire + 60); // take some margin here

        $query = "DELETE FROM $table
                        WHERE type = ? AND time < ?";
        $bindvars = [$this->type, $time];
        $stmt = $this->dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        $this->lastkey = null;
    }

    public function getCacheInfo()
    {
        $table = $this->getTable();
        if (empty($table)) {
            return [];
        }

        $query = "SELECT SUM(size), COUNT(id), MAX(time)
                   FROM $table
                   WHERE type = ?";
        $bindvars = [$this->type];
        $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if ($result->first()) {
            [$size, $count, $time] = $result->fields;
            $this->size = $size;
            $this->items = $count;
            $this->modtime = $time;
        }
        $result->close();

        return ['size'    => $this->size,
                'items'   => $this->items,
                'hits'    => $this->hits,
                'misses'  => $this->misses,
                'modtime' => $this->modtime];
    }

    public function saveFile($key = '', $filename = '')
    {
        if (empty($filename)) {
            return;
        }

        if ($key == $this->lastkey && isset($this->value)) {
            $value = $this->value;
        } else {
            $value = $this->getCached($key);
        }
        if (empty($value)) {
            return;
        }

        $tmp_file = $filename . '.tmp';

        $fp = @fopen($tmp_file, "w");
        if (!empty($fp)) {
            @fwrite($fp, $value);
            @fclose($fp);
            // rename() doesn't overwrite existing files in Windows
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                @copy($tmp_file, $filename);
                @unlink($tmp_file);
            } else {
                @rename($tmp_file, $filename);
            }
        }
    }

    public function getCachedList()
    {
        $table = $this->getTable();
        if (empty($table)) {
            return [];
        }

        $query = "SELECT id, time, cache_key, code, size, cache_check
                  FROM $table
                  WHERE type = ?";
        $bindvars = [$this->type];
        $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $list = [];
        while ($result->next()) {
            [$id, $time, $key, $code, $size, $check] = $result->fields;
            $list[$id] = ['key'   => $key,
                          'code'  => $code,
                          'time'  => $time,
                          'size'  => $size,
                          'check' => $check];
        }
        $result->close();
        return $list;
    }

    public function getCachedKeys()
    {
        $table = $this->getTable();
        if (empty($table)) {
            return [];
        }

        $query = "SELECT cache_key, COUNT(*) as count
                  FROM $table
                  WHERE type = ?
                  GROUP BY cache_key";
        $bindvars = [$this->type];
        $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $list = [];
        while ($result->next()) {
            [$key, $count] = $result->fields;
            $list[$key] = $count;
        }
        $result->close();
        return $list;
    }
}
