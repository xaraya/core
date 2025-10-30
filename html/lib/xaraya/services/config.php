<?php

/**
 * Config variables available via methods (WIP)
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

namespace Xaraya\Services;

use xarCore;
use xarSystemVars;
use sys;
use Exception;
use VariableNotFoundException;

/**
 * For documentation purposes only - available via ConfigTrait
 */
interface ConfigInterface extends ServiceInterface
{
    public const SLICE = 'config';

    public function getVar(string $varName, mixed $default = null): mixed;
    public function setVar(string $varName, mixed $value): bool;
    public function delVar(string $varName): mixed;
    public function cacheVars(?string $source = null): void;
}

/**
 * Config variables available via methods
 */
trait ConfigTrait
{
    use ServiceTrait;
    public const SCOPE = 'Config.Variables';
    private $preloaded = false;

    /**
     * Get config variable
     */
    public function getVar(string $varName, mixed $default = null): mixed
    {
        // --- LEGACY METHOD BODY ---
        // Preload the config vars once
        if (!$this->preloaded) {
            $this->preload();
        }

        if (!$this->preloaded) {
            throw new VariableNotFoundException($varName, "Variable #(1) not found");
        }

        // Configvars which are not in the database (either in config file or in code defines)
        switch ($varName) {
            case 'Site.DB.TablePrefix':
                return xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix');
            case 'System.Core.Generation':
                return xarCore::GENERATION;
            case 'System.Core.VersionNumber':
                return xarCore::VERSION_NUM;
            case 'System.Core.VersionId':
                return xarCore::VERSION_ID;
            case 'System.Core.VersionSub':
                return xarCore::VERSION_SUB;
            case 'prefix':
                // FIXME: Can we do this another way (dependency)
                return $this->getParent()->db()->getPrefix();
        }

        $mem = $this->getParent()->mem();
        // From the cache
        if ($mem->has(self::SCOPE, $varName)) {
            $value = $mem->get(self::SCOPE, $varName);
            return $value;
        }
        $value = $default;

        $db = $this->getParent()->db();
        // Need to retrieve it
        // @todo checkme What should we do here? preload again, or just fetch the one?
        $dbconn = $db->getConn();
        $tables = $db->getTables();
        $varstable = $tables['config_vars'] ?? null;
        // No tables, probably installing
        if ($varstable == null) {
            throw new VariableNotFoundException($varName, "Variable #(1) not found (no tables found, in fact)");
        }

        $query = "SELECT name, value FROM $varstable WHERE module_id is null AND name = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([$varName], $db->getFetchNum());
        if ($result->next()) {
            // Found it, retrieve and cache it
            $value = $result->get(2);
            $value = unserialize((string) $value);
            $mem->set(self::SCOPE, $result->getString(1), $value);
            $result->close();
            return $value;
        }

        // @todo: We found nothing, return the default if we had one
        if ($value !== null) {
            return $value;
        }
        throw new VariableNotFoundException($varName, "Variable #(1) not found");
        // --- END LEGACY METHOD BODY ---
        // return xarConfigVars::get(null, $varName, $value);
    }

    /**
     * Set config variable
     */
    public function setVar(string $varName, mixed $value): bool
    {
        // --- LEGACY METHOD BODY ---
        // FIXME: do we really want that ?
        // This way, worst case: 3 queries:
        // 1. deleting it
        // 2. Getting a new id (for some backends)
        // 3. inserting it.
        // Question is wether we want to invent new configvars on the fly or not
        $this->delVar($varName);

        $db = $this->getParent()->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();
        $config_varsTable = $tables['config_vars'];

        //Here we serialize the configuration variables
        //so they can effectively contain more than one value
        $serialvalue = serialize($value);

        //Insert
        $query = "INSERT INTO $config_varsTable
                  (module_id, name, value)
                  VALUES (?,?,?)";
        $bindvars = [null, $varName, $serialvalue];
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        $mem = $this->getParent()->mem();
        $mem->set(self::SCOPE, $varName, $value);

        return true;
        // --- END LEGACY METHOD BODY ---
        // return xarConfigVars::set(null, $varName, $value);
    }

    /**
     * Delete config variable
     */
    public function delVar(string $varName): bool
    {
        // --- LEGACY METHOD BODY ---
        $db = $this->getParent()->db();
        $dbconn = $db->getConn();
        $tables = $db->getTables();
        $config_varsTable = $tables['config_vars'];
        $query = "DELETE FROM $config_varsTable WHERE name = ? AND module_id is null";

        // We want to make the next two statements atomic
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate([$varName]);

        $mem = $this->getParent()->mem();
        $mem->del(self::SCOPE, $varName);

        return true;
        // --- END LEGACY METHOD BODY ---
        // return xarConfigVars::delete(null, $varName);
    }

    /**
     * Cache config variables
     */
    public function cacheVars(?string $source = null): void
    {
        // --- LEGACY METHOD BODY ---
        $mem = $this->getParent()->mem();
        if ($mem->hasPreload(self::SCOPE)) {
            $source ??= __METHOD__;
            $mem->save(self::SCOPE, null, $source);
        }
        // Saved in Base > Modify Configuration = modules/base/admingui/modifyconfig.php
        //xar::config()->cacheVars();
        // --- END LEGACY METHOD BODY ---
        // $source ??= __CLASS__ . '::' . __FUNCTION__;
        // xarConfigVars::cache($source);
    }

    protected function preload()
    {
        // --- LEGACY METHOD BODY ---
        $mem = $this->getParent()->mem();
        if ($mem->hasPreload(self::SCOPE) && $mem->load(self::SCOPE)) {
            $this->preloaded = true;
            return true;
        }

        $db = $this->getParent()->db();
        try {
            $dbconn = $db->getConn();
            $tables = $db->getTables();
            $varstable = $db->getPrefix() . '_module_vars';
        } catch (Exception $e) {
            return false;
        }

        $query = "SELECT name, value FROM $varstable WHERE module_id is null";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([], $db->getFetchAssoc());
        while ($result->next()) {
            $newval = unserialize($result->getString('value'));

            $val = $result->getString('value') ?? 's:0:""';
            $newval = unserialize($val);
            $mem->set(self::SCOPE, $result->getString('name'), $newval);
        }
        $result->close();

        if ($mem->hasPreload(self::SCOPE)) {
            $mem->save(self::SCOPE);
        }

        $this->preloaded = true;
        return true;
        // --- END LEGACY METHOD BODY ---
        // xarConfigVars::preload();
    }
}

/**
 * Access xarConfigVars::* Config methods (getVar, setVar, ...)
 *
 * Available methods:
 * - getVar()
 * - setVar()
 * - delVar()
 * - cache()
 * - ...
 *
 */
class ConfigService implements ConfigInterface
{
    use ConfigTrait;
}
