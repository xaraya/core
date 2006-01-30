<?php

/**
 * Cache data in the database using the xar_cache_data table
 */

class xarCache_Database_Storage extends xarCache_Storage
{
    var $table = '';
    var $lastkey = null;
    var $lastid = null;
    var $value = null;

    function xarCache_Database_Storage($args = array())
    {
        $this->xarCache_Storage($args);

        $this->storage = 'database';
    }

    function getTable()
    {
        if (!empty($this->table)) {
            return $this->table;

        } elseif (function_exists('xarDBGetSiteTablePrefix')) {
            $this->table = xarDBGetSiteTablePrefix() . '_cache_data';
            return $this->table;

        } else {
            // can't use this storage until the core is loaded !
        }
    }

    function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $table = $this->getTable();
        if (empty($table)) return false;

        $dbconn =& xarDBGetConn();
        // we actually retrieve the value here too
        $query = "SELECT xar_id, xar_time, xar_size, xar_check, xar_data
                  FROM $table
                  WHERE xar_type = ? AND xar_key = ? AND xar_code = ?";
        $bindvars = array($this->type, $key, $this->code);
        $result =& $dbconn->Execute($query, $bindvars);

        if (!$result) return;

        $this->lastkey = $key;

        if ($result->EOF) {
            $result->Close();
            $this->lastid = null;
            $this->value = null;
            if ($log) $this->logStatus('MISS', $key);
            return false;
        }
        list($id,$time,$size,$check,$data) = $result->fields;
        $result->Close();

        // TODO: process $size and $check if compressed ?

        $this->lastid = $id;
        if (!empty($expire) && $time < time() - $expire) {
            $this->value = null;
            if ($log) $this->logStatus('MISS', $key);
            return false;
        } else {
            $this->value = $data;
            $this->modtime = $time;
            if ($log) $this->logStatus('HIT', $key);
            return true;
        }
    }

    function getCached($key = '', $output = 0, $expire = 0)
    {
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
        if (empty($table)) return;

        $dbconn =& xarDBGetConn();
        $query = "SELECT xar_id, xar_time, xar_size, xar_check, xar_data
                  FROM $table
                  WHERE xar_type = ? AND xar_key = ? AND xar_code = ?";
        $bindvars = array($this->type, $key, $this->code);
        $result =& $dbconn->Execute($query, $bindvars);

        if (!$result) return;

        $this->lastkey = $key;

        if ($result->EOF) {
            $result->Close();
            $this->lastid = null;
            $this->value = null;
            return;
        }
        list($id,$time,$size,$check,$data) = $result->fields;
        $result->Close();

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

    function setCached($key = '', $value = '', $expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $time = time();
        $size = strlen($value);
        if ($this->compressed) {
            $check = crc32($value);
        } else {
            $check = '';
        }

        $table = $this->getTable();
        if (empty($table)) return;

        $dbconn =& xarDBGetConn();

        // TODO: is a transaction warranted here?
        // Since we catch the exception if someone beat us to it, a transaction could
        // cause a deadlock here? 
        if ($key == $this->lastkey && !empty($this->lastid)) {
            $query = "UPDATE $table
                         SET xar_time = ?,
                             xar_size = ?,
                             xar_check = ?,
                             xar_data = ?
                       WHERE xar_id = ?";
            $bindvars = array($time, $size, $check, $value, (int) $this->lastid);
            $dbconn->Execute($query, $bindvars);
        } else {
            try {
                $nextid = $dbconn->GenId($table);
                $query = "INSERT INTO $table (xar_id, xar_type, xar_key, xar_code, xar_time, xar_size, xar_check, xar_data)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $bindvars = array((int) $nextid, $this->type, $key, $this->code, $time, $size, $check, $value);
                $dbconn->Execute($query, $bindvars);
            } catch (SQLException $e) {
                // someone else beat us to it - ignore error
            }

        }
        $this->lastkey = null;
    }

    function delCached($key = '')
    {
        $table = $this->getTable();
        if (empty($table)) return;

        $dbconn =& xarDBGetConn();

        if ($key == $this->lastkey && !empty($this->lastid)) {
            $query = "DELETE FROM $table
                            WHERE xar_id = ?";
            $bindvars = array((int) $this->lastid);
            $dbconn->Execute($query, $bindvars);
        } else {
            $query = "DELETE FROM $table
                            WHERE xar_type = ? AND xar_key = ? AND xar_code = ?";
            $bindvars = array($this->type, $key, $this->code);
            $dbconn->Execute($query, $bindvars);
        }
        $this->lastkey = null;
    }

    function flushCached($key = '')
    {
        $table = $this->getTable();
        if (empty($table)) return;

        $dbconn =& xarDBGetConn();

        if (empty($key)) {
            $query = "DELETE FROM $table
                            WHERE xar_type = ?";
            $bindvars = array($this->type);
        } else {
            $key = '%'.$key.'%';
            $query = "DELETE FROM $table  WHERE xar_type = ? AND xar_key LIKE ?";
            $bindvars = array($this->type,$key);
        }
        $result =& $dbconn->Execute($query, $bindvars);
        if (!$result) return;

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
        $this->lastkey = null;
    }

    function cleanCached($expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        if (empty($expire)) {
            // TODO: delete oldest entries if we're at the size limit ?
            return;
        }
        $table = $this->getTable();
        if (empty($table)) return;

        $touch_file = $this->cachedir . '/cache.' . $this->type . 'level';

        // If the cache type has already been cleaned within the expiration time,
        // don't bother checking again
        if (file_exists($touch_file) && filemtime($touch_file) > time() - $expire) {
            return;
        }
        if (!@touch($touch_file)) {
            // hmm, somthings amiss... better let the administrator know,
            // without disrupting the site
            error_log('Error from Xaraya::xarCache::storage::filesystem
                      - web process can not touch ' . $touch_file);
        }

        $dbconn =& xarDBGetConn();

        $time = time() - ($expire + 60); // take some margin here

        $query = "DELETE FROM $table
                        WHERE xar_type = ? AND xar_time < ?";
        $bindvars = array($this->type, $time);
        $result =& $dbconn->Execute($query, $bindvars);
        if (!$result) return;

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
        $this->lastkey = null;
    }

    function getCacheSize($countitems = false)
    {
        $table = $this->getTable();
        if (empty($table)) return;

        $dbconn =& xarDBGetConn();

        if ($countitems) {
            $query = "SELECT SUM(xar_size), COUNT(xar_id)
                        FROM $table
                       WHERE xar_type = ?";
            $bindvars = array($this->type);
            $result =& $dbconn->Execute($query, $bindvars);
            if (!$result) return;

            list($size,$count) = $result->fields;
            $result->Close();

            $this->numitems = $count;
        } else {
            $query = "SELECT SUM(xar_size)
                        FROM $table
                       WHERE xar_type = ?";
            $bindvars = array($this->type);
            $result =& $dbconn->Execute($query, $bindvars);
            if (!$result) return;

            list($size) = $result->fields;
            $result->Close();
        }

        $this->size = $size;
        return $size;
    }

    function saveFile($key = '', $filename = '')
    {
        if (empty($filename)) return;

        if ($key == $this->lastkey && isset($this->value)) {
            $value = $this->value;
        } else {
            $value = $this->getCached($key);
        }
        if (empty($value)) return;

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

    function getCachedList()
    {
        $table = $this->getTable();
        if (empty($table)) return false;

        $dbconn =& xarDBGetConn();
        // we actually retrieve the value here too
        $query = "SELECT xar_id, xar_time, xar_key, xar_code, xar_size, xar_check
                  FROM $table
                  WHERE xar_type = ?";
        $bindvars = array($this->type);
        $result =& $dbconn->Execute($query, $bindvars);

        if (!$result) return;

        $list = array();
        while (!$result->EOF) {
            list($id,$time,$key,$code,$size,$check) = $result->fields;
            $list[$id] = array('key'   => $key,
                               'code'  => $code,
                               'time'  => $time,
                               'size'  => $size,
                               'check' => $check);
            $result->MoveNext();
        }
        $result->Close();
        return $list;
    }

}

?>
