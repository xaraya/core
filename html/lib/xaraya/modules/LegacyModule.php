<?php

/**
 * Legacy module without any components or methods
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use Xaraya\Services\Modules\InfoHelper;
use ixarMod;
use sys;
use Exception;
use FunctionNotFoundException;
use ModuleNotActiveException;
use ModuleNotFoundException;

/**
 * Legacy module without any components or methods
 * @todo replace fallback methods in InfoHelper/ExecHelper/... someday
 */
class LegacyModule implements ModuleInterface
{
    use ModuleTrait;

    /**
     * Get tables from xartables.php (2.4.*)
     * @return array<string, mixed>
     */
    public function getTables(): array
    {
        // Load the database definition if required
        try {
            include_once sys::code() . 'modules/' . $this->getModName() . '/xartables.php';
        } catch (Exception $e) {
            return [];
        }
        $tablefunc = $this->getModName() . '_' . 'xartables';
        if (function_exists($tablefunc)) {
            $xar = $this->getServicesClass();
            // pass along the DB prefix to $tablefunc
            $prefix = $xar->db()->getPrefix();
            return $tablefunc($prefix);
        }
        return [];
    }

    public function getClassName(string $fileType)
    {
        // always returns LegacyModuleClass here
        return LegacyModuleClass::class;
    }

    public function getComponent(string $classType): ?ModuleClassInterface
    {
        // convert $classType back to old-school user/userapi format for file match
        $fileType = $this->getClassType($classType);
        if (empty($fileType)) {
            //throw new FunctionNotFoundException($this->getModName() . ':' . $classType);
            return null;
        }
        // we need to use modname + modtype here
        $cacheKey = $this->getModName() . ':' . $fileType;
        if (!array_key_exists($cacheKey, $this->components)) {
            try {
                // always returns LegacyModuleClass here
                $className = $this->getClassName($fileType);
                if (class_exists($className)) {
                    $this->components[$cacheKey] = $this->createComponent($className);
                    // we need to set modtype here
                    $this->components[$cacheKey]->setModType($fileType);
                } else {
                    $this->components[$cacheKey] = null;
                }
            } catch (\Throwable $e) {
                throw new Exception("Unable to create '$className': " . $e->getMessage(), 0, $e);
            }
        }
        return $this->components[$cacheKey];
    }

    public function hasComponent(string $fileType): bool
    {
        $modDir = $this->getModName();
        $fileName = sys::code() . 'modules/' . $modDir . '/xar' . $fileType . '.php';
        if (file_exists($fileName)) {
            // we import the shared xar<type>.php file here
            sys::import('modules.' . $modDir . '.xar' . $fileType);
            return true;
        }
        $dirName = sys::code() . 'modules/' . $modDir . '/xar' . $fileType;
        if (is_dir($dirName)) {
            return true;
        }
        return false;
    }

    /**
     * Convert $modType back to old-school user/userapi format for file match
     */
    public function getClassType(string $modType): ?string
    {
        $modDir = $this->getModName();
        $modType = strtolower($modType);
        // we need to use modname + modtype here
        $cacheKey = $modDir . ':' . $modType;
        if (isset($this->classtypes[$cacheKey])) {
            return $this->classtypes[$cacheKey];
        }
        $fileType = $modType;
        // remove gui part here if present
        if (str_ends_with($fileType, 'gui')) {
            $fileType = substr($fileType, 0, -3);
        }
        if ($this->hasComponent($fileType)) {
            $this->classtypes[$cacheKey] = $fileType;
            return $this->classtypes[$cacheKey];
        }
        $this->classtypes[$cacheKey] = '';
        // no class types available here
        return null;
    }

    /**
     * @see \xar::mod()->getModuleClassMethod()
     */
    public function getCallableMethod(string $modType, string $funcName, string $callType = 'api'): ?callable
    {
        // $modType already includes $funcType here, e.g. userapi or installer
        $fileType = $this->getClassType($modType);
        if (empty($fileType)) {
            return null;
        }
        // we re-use $modType as argument here to support userapi() etc.
        $component = $this->getComponent($modType);
        if (!isset($component)) {
            return null;
        }
        if ($component->hasMethod($funcName, $callType)) {
            // Build function name
            $modFunc = $component->getModFunc($funcName);
            // use function name as callable here
            if (is_callable($modFunc)) {
                //return [$component, $funcName];
                return $modFunc;
            }
        }
        // no callable methods available here
        return null;
    }

    /**
     * Check module state and version on demand
     * Note: file check and sys::import are already done in LegacyModule::getClassType()
     * @param mixed $flags
     * @throws ModuleNotFoundException
     * @throws ModuleNotActiveException
     * @return bool
     */
    public function checkState($flags = ixarMod::LOAD_ANYSTATE): bool
    {
        $modName = $this->getModName();

        // Allow inactive/non-upgraded modules in any state
        if ($flags & ixarMod::LOAD_ANYSTATE) {
            $modBaseInfo = $this->getFileInfo();
            // Not a valid module - throw exception
            if (empty($modBaseInfo)) {
                throw new ModuleNotFoundException($modName, 'The module "#(1)" cannot be found.');
            }
            return true;
        }

        $xar = $this->getServicesClass();
        /** @var InfoHelper $info */
        $info = $xar->service('modules.info');

        $modBaseInfo = $info->getBaseInfo($modName);
        // Not a valid module - throw exception
        if (empty($modBaseInfo)) {
            throw new ModuleNotFoundException($modName, 'The module "#(1)" cannot be found.');
        }
        // Not a valid module state - throw exception
        if ($modBaseInfo['state'] != ixarMod::STATE_ACTIVE) {
            throw new ModuleNotActiveException($modName);
        }
        // Not the correct version - throw exception unless we are upgrading
        if (!$info->checkVersion($modName) && !$xar->mem()->get('Upgrade', 'upgrading') && $modName != 'modules') {
            $xar->exit('The core module "' . $modName . '" does not have the correct version. Please run the upgrade routine by clicking <a href="upgrade.php">here</a>');
            return false;
        }
        return true;
    }
}
