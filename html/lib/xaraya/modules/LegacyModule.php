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
use sys;
use Exception;

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

    public function getClassName(string $classType)
    {
        // always returns LegacyModuleClass here
        return LegacyModuleClass::class;
    }

    public function getComponent(string $classType): ?ModuleClassInterface
    {
        // convert $classType back to old-school user/userapi format for file match
        $classType = $this->getClassType($classType);
        // we need to use modname + modtype here
        $cacheKey = $this->getModName() . ':' . $classType;
        if (!array_key_exists($cacheKey, $this->components)) {
            try {
                // always returns LegacyModuleClass here
                $className = $this->getClassName($classType);
                if (class_exists($className)) {
                    $this->components[$cacheKey] = $this->createComponent($className);
                    // we need to set modtype here
                    $this->components[$cacheKey]->setModType($classType);
                } else {
                    $this->components[$cacheKey] = null;
                }
            } catch (\Throwable $e) {
                throw new Exception("Unable to create '$className': " . $e->getMessage(), 0, $e);
            }
        }
        return $this->components[$cacheKey];
    }

    /**
     * Convert $modType back to old-school user/userapi format for file match
     */
    public function getClassType(string $modType): ?string
    {
        $modDir = $this->getModName();
        $modType = strtolower($modType);
        // remove gui part here if present
        if (str_ends_with($modType, 'gui')) {
            $modType = substr($modType, 0, -3);
        }
        $fileName = sys::code() . 'modules/' . $modDir . '/xar' . $modType . '.php';
        if (file_exists($fileName)) {
            // return modtype here
            return $modType;
        }
        $dirName = sys::code() . 'modules/' . $modDir . '/xar' . $modType;
        if (is_dir($dirName)) {
            // return modtype here
            return $modType;
        }
        // no class types available here
        return null;
    }

    /**
     * @see \xar::mod()->getModuleClassMethod()
     */
    public function getCallableMethod(string $modType, string $funcName, string $callType = 'api'): ?callable
    {
        // $modType already includes $funcType here, e.g. userapi or installer
        $classType = $this->getClassType($modType);
        if (!isset($classType)) {
            return null;
        }
        $component = $this->getComponent($classType);
        if (!isset($component)) {
            return null;
        }
        if ($component->hasMethod($funcName, $callType)) {
            $modName = $this->getModName();
            // Build function name
            $modFunc = "{$modName}_{$modType}_{$funcName}";
            // use function name as callable here
            if (is_callable($modFunc)) {
                return $modFunc;
            }
        }
        // no callable methods available here
        return null;
    }
}
