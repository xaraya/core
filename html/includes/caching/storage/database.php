<?php

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

    function isCached($key = '')
    {
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
            $this->logStatus('MISS', $key);
            return false;
        }
        list($id,$time,$size,$check,$data) = $result->fields;
        $result->Close();

        // TODO: process $size and $check if compressed ?

        $this->lastid = $id;
        if (!empty($this->expire) && $time < time() - $this->expire) {
            $this->value = null;
            $this->logStatus('MISS', $key);
            return false;
        } else {
            $this->value = $data;
            $this->modtime = $time;
            $this->logStatus('HIT', $key);
            return true;
        }
    }

    function getCached($key = '')
    {
        if ($key == $this->lastkey && isset($this->value)) {
            $this->lastkey = null;
            return $this->value;
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
            return false;
        }
        list($id,$time,$size,$check,$data) = $result->fields;
        $result->Close();

        // TODO: process $size and $check if compressed ?

        $this->lastid = $id;
        if (!empty($this->expire) && $time < time() - $this->expire) {
            return;
        } else {
            return $data;
        }
    }

    function setCached($key = '', $value = '')
    {
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

        if ($key == $this->lastkey && !empty($this->lastid)) {
            $query = "UPDATE $table
                         SET xar_time = ?,
                             xar_size = ?,
                             xar_check = ?,
                             xar_data = ?
                       WHERE xar_id = ?";
            $bindvars = array($time, $size, $check, $value, (int) $this->lastid);
            $result =& $dbconn->Execute($query, $bindvars);
            if (!$result) return;
        } else {
            $nextid = $dbconn->GenId($table);
            $query = "INSERT INTO $table (xar_id, xar_type, xar_key, xar_code, xar_time, xar_size, xar_check, xar_data)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $bindvars = array((int) $nextid, $this->type, $key, $this->code, $time, $size, $check, $value);
            $result =& $dbconn->Execute($query, $bindvars);
            if (!$result) {
                // someone else beat us to it - ignore error
                xarErrorHandled();
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
            $result =& $dbconn->Execute($query, $bindvars);
            if (!$result) return;
        } else {
            $query = "DELETE FROM $table
                            WHERE xar_type = ? AND xar_key = ? AND xar_code = ?";
            $bindvars = array($this->type, $key, $this->code);
            $result =& $dbconn->Execute($query, $bindvars);
            if (!$result) return;
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
            $key = $dbconn->qstr('%' . $key . '%');
            $query = "DELETE FROM $table
                            WHERE xar_type = ? AND xar_key LIKE $key";
            $bindvars = array($this->type);
        }
        $result =& $dbconn->Execute($query, $bindvars);
        if (!$result) return;
        $this->lastkey = null;
    }

    function cleanCached()
    {
        if (empty($this->expire)) {
            // TODO: delete oldest entries if we're at the size limit ?
            return;
        }
        $table = $this->getTable();
        if (empty($table)) return;

        $dbconn =& xarDBGetConn();

        $time = time() - ($this->expire + 60); // take some margin here

        $query = "DELETE FROM $table
                        WHERE xar_type = ? AND xar_time < ?";
        $bindvars = array($this->type, $time);
        $result =& $dbconn->Execute($query, $bindvars);
        if (!$result) return;

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
}

?>
