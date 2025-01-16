<?php

/**
 * Modules available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarMod;
use xarModVars;
use sys;
use Exception;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via ModulesTrait
 */
interface ModulesInterface extends ServiceInterface
{
    public function getVar(string $varName): mixed;
    public function setVar(string $varName, mixed $value): bool;
    public function getRegId(?string $modName = null): int;
    /** @return array<string, mixed> */
    public function getInfo(?string $modName = null): array;
    /** @return array<string, mixed> */
    public function getTables(): array;
}

/**
 * Modules available via methods
 * @template TParent of ServicesInterface
 */
trait ModulesTrait
{
    /** @use ServiceTrait<TParent> */
    use ServiceTrait;

    /**
     * Get module variable for this module
     */
    public function getVar(string $varName): mixed
    {
        return xarModVars::get($this->getModName(), $varName);
    }

    /**
     * Set module variable for this module, or delete if value = null
     */
    public function setVar(string $varName, mixed $value): bool
    {
        if (is_null($value)) {
            return xarModVars::delete($this->getModName(), $varName);
        }
        return xarModVars::set($this->getModName(), $varName, $value);
    }

    /**
     * Get module registry ID for this module
     */
    public function getRegId(?string $modName = null): int
    {
        // avoid getting module id from xarMod::getRegID() here
        //return xarMod::getRegId($this->getModName());
        $fileInfo = $this->getInfo($modName);
        return (int) $fileInfo['regid'];
    }

    /**
     * Get info from xarversion.php
     * @return array<string, mixed>
     */
    public function getInfo(?string $modName = null): array
    {
        $modName ??= $this->getModName();
        return xarMod::getFileInfo($modName) ?? [];
    }

    /**
     * Get tables from xartables.php
     * @return array<string, mixed>
     */
    public function getTables(?string $modName = null): array
    {
        $modName ??= $this->getModName();
        // Load the database definition if required
        try {
            sys::import('modules.' . $modName . '.xartables');
        } catch (Exception $e) {
            return [];
        }
        $tablefunc = $modName . '_' . 'xartables';
        if (function_exists($tablefunc)) {
            // xarDB::importTables($tablefunc());
            return $tablefunc();
        }
        return [];
    }

    /**
     * Wrapper for xarMod::apiFunc() - only for migration
     * @param string $type
     * @param string $func
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function callAPI($type, $func, $args = [])
    {
        return xarMod::apiFunc($this->getModName(), $type, $func, $args, $this->getContext());
    }

    /**
     * Wrapper for xarMod::guiFunc() - only for migration
     * @param string $type
     * @param string $func
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function callGUI($type, $func, $args = [])
    {
        return xarMod::guiFunc($this->getModName(), $type, $func, $args, $this->getContext());
    }
}

/**
 * Access xarMod*::* Modules methods (getVar, setVar, ...)
 *
 * Available methods:
 * - getVar()
 * - setVar()
 * - ...
 *
 * Required methods in parent:
 * - getModName()
 * - getItemType() for xMod()->module()
 * - getModType() for xMod()->module()
 *
 * @template TParent of ServicesInterface
 */
class ModulesService implements ModulesInterface
{
    /** @use ModulesTrait<TParent> */
    use ModulesTrait;

    /**
     * Get name of the module from parent
     */
    public function getModName(): string
    {
        return $this->getParent()->getModName();
    }

    /**
     * Get item type from parent
     */
    public function getItemType(): int
    {
        return $this->getParent()->getItemType();
    }

    /**
     * Get module type (user, admin, ...) from parent
     */
    public function getModType(): string
    {
        return $this->getParent()->getModType();
    }
}
