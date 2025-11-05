<?php

/**
 * Modules Service Helper for Module Variables
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

use Xaraya\Services\ServiceClass;
use EmptyParameterException;
use SQLException;

/**
 * Modules Service Helper for Module Variables
 */
class VarsHelper extends ServiceClass
{
    public const SLICE = 'modules.vars';
    public const SCOPE = 'Mod.Variables';

    private $preloaded = []; // Keep track of what module vars (per module) we already had

    public function get(string $modName, string $varName): mixed
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        if (empty($varName)) {
            throw new EmptyParameterException('name');
        }

        // Preload per module, once
        if (!isset($this->preloaded[$modName])) {
            $this->preload($modName);
        }

        // Lets first check to see if any of our type vars are already set in the cache.
        $cacheScope = self::SCOPE . '.' . $modName;

        $mem = $this->getParent()->mem();
        // Try to get it from the cache
        if ($mem->has($cacheScope, $varName)) {
            $value = $mem->get($cacheScope, $varName);
            return $value;
        }

        $mod = $this->getParent()->mod();
        // Still no luck, let's do the hard work then
        $modBaseInfo = $mod->getBaseInfo($modName);
        if (empty($modBaseInfo)) {
            return null;
        }

        $db = $this->getParent()->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();

        // Retrieve all the variables for this module at once
        $module_varstable = $tables['module_vars'];
        $query = "SELECT name, value FROM $module_varstable WHERE module_id = ? AND name = ?";
        $bindvars = [(int) $modBaseInfo['systemid'], $varName];

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, $db->getFetchNum());

        if ($result->next()) {
            // Found
            $value = $result->get(2);
            $mem->set($cacheScope, $result->getString(1), $value);
        } else {
            $value = null;
        }
        $result->close();
        return $value;
    }

    public function set(string $modName, string $varName, mixed $value): bool
    {
        // @checkme: this is new behavior for template functions
        if (is_null($value)) {
            return $this->delete($modName, $varName);
        }
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        if (empty($varName)) {
            throw new EmptyParameterException('name');
        }

        $db = $this->getParent()->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();
        $module_varstable = $tables['module_vars'];

        $mod = $this->getParent()->mod();
        $modBaseInfo = $mod->getBaseInfo($modName);

        // We need the variable id
        //unset($modvarid);
        $modvarid = $this->getID($modName, $varName);

        if ($value === false) {
            $value = 0;
        }
        if ($value === true) {
            $value = 1;
        }
        if (!$modvarid) {
            // Not there yet
            $query = "INSERT INTO $module_varstable
                         (module_id, name, value)
                      VALUES (?,?,?)";
            $bindvars = [$modBaseInfo['systemid'], $varName, (string) $value];
        } else {
            // Existing one
            $query = "UPDATE $module_varstable SET value = ? WHERE id = ?";
            $bindvars = [(string) $value, $modvarid];
        }
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        // Update cache for the variable
        $cacheScope = self::SCOPE . '.' . $modName;
        $mem = $this->getParent()->mem();
        $mem->set($cacheScope, $varName, $value);
        return true;
    }

    public function delete(string $modName, string $varName): bool
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        $db = $this->getParent()->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();

        $mod = $this->getParent()->mod();
        $modBaseInfo = $mod->getBaseInfo($modName);

        // Delete all the itemvars derived from this var first
        $modvarid = $this->getID($modName, $varName);
        // TODO: we should delegate this to moditemvars class somehow
        if ($modvarid) {
            $module_itemvarstable = $tables['module_itemvars'];
            $query = "DELETE FROM $module_itemvarstable WHERE module_var_id = ?";
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate([(int) $modvarid]);
        }

        // Now delete the modvar itself
        $module_varstable = $tables['module_vars'];
        // Now delete the module var itself
        $query = "DELETE FROM $module_varstable WHERE module_id = ? AND name = ?";
        $bindvars = [$modBaseInfo['systemid'], $varName];
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        // Removed it from the cache
        $cacheScope = self::SCOPE . '.' . $modName;
        $mem = $this->getParent()->mem();
        $mem->del($cacheScope, $varName);
        return true;
    }

    public function cache(string $modName, ?string $source = null): void
    {
        $cacheScope = self::SCOPE . '.' . $modName;
        $mem = $this->getParent()->mem();
        if ($mem->hasPreload($cacheScope)) {
            $source ??= __METHOD__;
            $mem->save($cacheScope, null, $source);
        }
        // Saved in DD > Modify Configuration = modules/dynamicdata/admingui/modifyconfig.php
        //xar::mod('dynamicdata')->cacheVars();
    }

    /**
     * Support function for xar::mod()->*ItemVar and *UserVar functions
     *
     * provides module variable id based on module name and variable name
     */
    public function getID(string $modName, string $varName): int
    {
        // Module name and variable name are both necesary
        if (empty($modName) || empty($varName)) {
            throw new EmptyParameterException('modName and/or name');
        }

        $mod = $this->getParent()->mod();
        // Retrieve module info, so we can decide where to look
        $modBaseInfo = $mod->getBaseInfo($modName);
        if (empty($modBaseInfo)) {
            return 0;
        } // throw back

        $cacheScope = 'Mod.GetVarID';
        $mem = $this->getParent()->mem();
        if ($mem->has($cacheScope, $modBaseInfo['name'] . $varName)) {
            return $mem->get($cacheScope, $modBaseInfo['name'] . $varName);
        }

        $db = $this->getParent()->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();

        $module_varstable = $tables['module_vars'];

        $query = "SELECT id FROM $module_varstable WHERE module_id = ? AND name = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([(int) $modBaseInfo['systemid'], $varName], $db->getFetchNum());
        // If there is no such thing, the callee is responsible, return null
        if (!$result->next()) {
            return 0;
        }

        // Return the ID
        $modvarid = $result->getInt(1);
        $result->Close();

        $mem->set($cacheScope, $modName . $varName, $modvarid);
        return $modvarid;
    }

    public function disableOverview(): bool
    {
        return $this->get('modules', 'disableoverview') ? true : false;
    }

    public function preload($modName): bool
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        $cacheScope = self::SCOPE . '.' . $modName;
        $mem = $this->getParent()->mem();
        if ($mem->hasPreload($cacheScope) && $mem->load($cacheScope)) {
            $this->preloaded[$modName] = true;
            return true;
        }

        $mod = $this->getParent()->mod();
        $modBaseInfo = $mod->getBaseInfo($modName);
        if (empty($modBaseInfo)) {
            return false;
        }

        $db = $this->getParent()->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();

        $module_varstable = $tables['module_vars'];

        $query = "SELECT name, value FROM $module_varstable WHERE module_id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([$modBaseInfo['systemid']], $db->getFetchAssoc());

        while ($result->next()) {
            $mem->set($cacheScope, $result->getString('name'), $result->get('value'));
        }
        $result->close();

        if ($mem->hasPreload($cacheScope)) {
            $mem->save($cacheScope);
        }

        $this->preloaded[$modName] = true;
        return true;
    }

    public function flush($modName): bool
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        $mod = $this->getParent()->mod();
        $modBaseInfo = $mod->getBaseInfo($modName);

        $db = $this->getParent()->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();

        $module_varstable     = $tables['module_vars'];
        $module_itemvarstable = $tables['module_itemvars'];

        // PostGres (allows only one table in DELETE)
        // MySql: multiple table delete only from 4.0 up
        // Select the id's which need to be removed
        $sql = "SELECT $module_varstable.id FROM $module_varstable WHERE $module_varstable.module_id = ?";
        $stmt = $dbconn->prepareStatement($sql);
        $result = $stmt->executeQuery([$modBaseInfo['systemid']], $db->getFetchNum());

        // Seems that at least mysql and pgsql support the scalar IN operator
        $idlist = [];
        while ($result->next()) {
            $idlist[] = $result->getInt(1);
        }
        $result->close();
        unset($result);

        // We delete the module vars and the user vars in a transaction, which either succeeds completely or totally fails
        try {
            $dbconn->begin();
            if (count($idlist) != 0) {
                $bindmarkers = '?' . str_repeat(',?', count($idlist) - 1);
                $sql = "DELETE FROM $module_itemvarstable WHERE $module_itemvarstable.module_var_id IN (" . $bindmarkers . ")";
                $stmt = $dbconn->prepareStatement($sql);
                $result = $stmt->executeUpdate($idlist);
            }

            // Now delete the module vars
            $query = "DELETE FROM $module_varstable WHERE module_id = ?";
            $stmt  = $dbconn->prepareStatement($query);
            $result = $stmt->executeUpdate([$modBaseInfo['systemid']]);
            $dbconn->commit();
        } catch (SQLException $e) {
            // If there was an SQL exception roll back to where we started
            $dbconn->rollback();
            // and raise it again so the handler catches
            // TODO: demote to error? raise other type of exception?
            throw $e;
        }
        return true;
    }
}
