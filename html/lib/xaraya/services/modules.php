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
use xarController;
use xarTpl;
use sys;
use Exception;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via ModulesTrait
 */
interface ModulesInterface extends ServiceInterface
{
    public function getVar(string $varName, ?string $modName = null): mixed;
    public function setVar(string $varName, mixed $value, ?string $modName = null): bool;
    /** @param array<string, mixed> $args */
    public function getURL(string $modType = 'user', string $funcName = 'main', array $args = [], ?string $modName = null): string;
    /** @param array<string, mixed> $tplData */
    public function template(string $funcName, array $tplData = [], ?string $templateName = null): string;
    /**
     * @param array<string, mixed> $tplData
     * @return array<string, mixed>
     */
    public function prepare(array $tplData = []): array;
    public function getRegId(?string $modName = null): int;
    /** @return array<string, mixed> */
    public function getInfo(?string $modName = null): array;
    /** @return array<string, mixed> */
    public function getTables(?string $modName = null): array;
    public function isAvailable(?string $modName = null): bool;
}

/**
 * Modules available via methods
 */
trait ModulesTrait
{
    use ServiceTrait;

    /**
     * Get module variable for this module
     */
    public function getVar(string $varName, ?string $modName = null): mixed
    {
        $modName ??= $this->getModName();
        return xarModVars::get($modName, $varName);
    }

    /**
     * Set module variable for this module, or delete if value = null
     */
    public function setVar(string $varName, mixed $value, ?string $modName = null): bool
    {
        $modName ??= $this->getModName();
        if (is_null($value)) {
            return xarModVars::delete($modName, $varName);
        }
        return xarModVars::set($modName, $varName, $value);
    }

    /**
     * Get url for this module type function
     * @param array<string, mixed> $args
     */
    public function getURL(string $modType = 'user', string $funcName = 'main', array $args = [], ?string $modName = null): string
    {
        $modName ??= $this->getModName();
        return xarController::URL($modName, $modType, $funcName, $args);
    }

    /**
     * Render output with module template
     * @uses xarTpl::module()
     * @param string $funcName
     * @param array<string, mixed> $tplData
     * @param ?string $templateName
     * @return string
     */
    public function template(string $funcName, array $tplData = [], ?string $templateName = null): string
    {
        // Add standard template variables (module, itemtype and context)
        $tplData = $this->prepare($tplData);

        // See if we have a special template to apply
        if (!isset($templateName) && isset($tplData['_bl_template'])) {
            $templateName = (string) $tplData['_bl_template'];
        }

        $modName = $this->getModName();
        // @todo Check if we're called from an api $modType and adapt to gui!?
        $modType = $this->getModType();
        if (str_ends_with($modType, 'api')) {
            $modType = substr($modType, 0, -3);
        }

        // Create the output.
        return xarTpl::module(
            $modName,
            $modType,
            $funcName,
            $tplData,
            $templateName
        );
    }

    /**
     * Add standard template variables (module, itemtype and context)
     * @param array<string, mixed> $tplData
     * @return array<string, mixed>
     */
    public function prepare(array $tplData = []): array
    {
        // Add standard template variables
        $tplData['module'] ??= $this->getModName();
        $tplData['itemtype'] ??= $this->getItemType();
        // Pass along the context for xarTpl::module() if needed
        $tplData['context'] ??= $this->getContext();
        return $tplData;
    }

    /**
     * Get module registry ID for this module
     */
    public function getRegId(?string $modName = null): int
    {
        $modName ??= $this->getModName();
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
     * @todo pass along the DB prefix to $tablefunc
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
            // @todo pass along the DB prefix to $tablefunc
            return $tablefunc();
        }
        return [];
    }

    /**
     * Check if a module is available - @todo review for module classes
     */
    public function isAvailable(?string $modName = null): bool
    {
        $modName ??= $this->getModName();
        return xarMod::isAvailable($modName) ? true : false;
    }

    /**
     * Wrapper for xarMod::apiFunc() - only for migration
     * @param ?string $modName
     * @param ?string $modType
     * @param string $funcName
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function apiFunc($modName = null, $modType = null, $funcName = 'main', $args = [])
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return xarMod::apiFunc($modName, $modType, $funcName, $args, $this->getContext());
    }

    /**
     * Wrapper for xarMod::apiLoad() - only for migration
     * @param ?string $modName
     * @param ?string $modType
     * @return mixed
     */
    public function apiLoad($modName = null, $modType = null)
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return xarMod::apiLoad($modName, $modType);
    }

    /**
     * Wrapper for xarMod::guiFunc() - only for migration
     * @param ?string $modName
     * @param ?string $modType
     * @param string $funcName
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function guiFunc($modName = null, $modType = null, $funcName = 'main', $args = [])
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return xarMod::guiFunc($modName, $modType, $funcName, $args, $this->getContext());
    }

    /**
     * Wrapper for xarMod::load() - only for migration
     * @param ?string $modName
     * @param ?string $modType
     * @return mixed
     */
    public function load($modName = null, $modType = null)
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return xarMod::load($modName, $modType);
    }
}

/**
 * Access xarMod*::* Modules methods (getVar, setVar, ...)
 *
 * Available methods:
 * - getVar()
 * - setVar()
 * - getURL() for current module - or use ctl()->URL() in general with modName
 * - template() for current module type - or use tpl()->module() in general with modName modType
 * - prepare() for current module itemtype
 * - getRegId()
 * - getInfo()
 * - ...
 *
 * Required methods in parent:
 * - getModName()
 * - getItemType() for mod()->prepare()
 * - getModType() for mod()->template()
 *
 */
class ModulesService implements ModulesInterface
{
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
