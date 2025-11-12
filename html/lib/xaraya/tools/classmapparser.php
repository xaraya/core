<?php

/**
 * Xaraya Class Map Parser
 *
 * @package core\classmap
 * @category Xaraya Web Applications Framework
 * @version 2.7.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Tools;

use sys;
use Throwable;

/**
 * Xaraya Class Map Parser of composer autoload_classmap
 */
class ClassMapParser
{
    /** @var array<string, array<mixed>> */
    protected array $classmap = [];
    protected bool $checkClass = true;

    public function __construct()
    {
        $this->classmap = [
            'autoload' => [],
            'modules' => [],
            'methods' => [],
            'blocks' => [],
            'properties' => [],
            'controllers' => [],
            'eventsubjects' => [],
            'eventobservers' => [],
            'hooksubjects' => [],
            'hookobservers' => [],
            'middleware' => [],
            'handlers' => [],
            'routes' => [],
            'dataobjects' => [],
            'tables' => [],
            'versions' => [],
            'classes' => [],
            'others' => [],
        ];
    }

    /**
     * Summary of parse
     * @return array<string, array<mixed>>
     */
    public function parse(string $file): array
    {
        $this->classmap['autoload'] = [
            'classmap' => $file,
            'generated' => date('c'),
        ];
        if (!file_exists($file)) {
            return $this->classmap;
        }
        $this->classmap['autoload']['updated'] = date('c', filemtime($file));
        if ($this->checkClass) {
            // we need this to check PSR-15 middleware classes, where autoload is default
        }
        $defined = require $file;
        // we are only looking for xaraya code here, not lib, themes etc.
        foreach ($defined as $className => $filePath) {
            if (str_contains($filePath, '/html/code/')) {
                $this->matchCodeFile($className, $filePath);
                continue;
            }
            if (str_contains($filePath, '/vendor/xaraya/')) {
                $this->matchVendorFile($className, $filePath);
                continue;
            }
        }
        ksort($this->classmap);
        foreach (array_keys($this->classmap) as $key) {
            ksort($this->classmap[$key]);
        }
        return $this->classmap;
    }

    protected function matchCodeFile(string $className, string $filePath): void
    {
        $match = [];
        if (preg_match('~/html/code/modules/(\w+)/(.+)$~', $filePath, $match)) {
            $modName = $match[1];
            $dirName = dirname($match[2]);
            $fileType = str_replace('.php', '', basename($match[2]));
            $this->addModuleFile($className, $filePath, $modName, $dirName, $fileType);
            return;
        }
        if (preg_match('~/html/code/properties/(\w+)/(.+)$~', $filePath, $match)) {
            $modName = '';
            $fileType = $match[1];
            $this->addProperty($className, $filePath, $modName, $fileType);
            return;
        }
        if (preg_match('~/html/code/blocks/(\w+)/(.+)$~', $filePath, $match)) {
            $modName = '';
            // @todo handle interface? This could be .php, _display.php or /admin.php - last one is not supported
            $fileType = $match[1];
            $this->addBlock($className, $filePath, $modName, $fileType);
            return;
        }
    }

    protected function matchVendorFile(string $className, string $filePath): void
    {
        $match = [];
        if (preg_match('~/vendor/xaraya/properties/(\w+)/(.+)$~', $filePath, $match)) {
            $modName = '';
            $fileType = $match[1];
            $this->addProperty($className, $filePath, $modName, $fileType);
            return;
        }
        if (preg_match('~/vendor/xaraya/(\w+)/(.+)$~', $filePath, $match)) {
            $modName = $match[1];
            $dirName = dirname($match[2]);
            $fileType = str_replace('.php', '', basename($match[2]));
            $this->addModuleFile($className, $filePath, $modName, $dirName, $fileType);
            return;
        }
    }

    protected function addModuleFile(string $className, string $filePath, string $modName, string $dirName, string $fileType): void
    {
        switch ($dirName) {
            // module files in top directory
            case '.':
                if ($fileType == 'tables') {
                    $this->addTables($className, $filePath, $modName, $fileType);
                } elseif ($fileType == 'version') {
                    $this->addVersion($className, $filePath, $modName, $fileType);
                } else {
                    $this->addModType($className, $filePath, $modName, $fileType);
                }
                return;
            case 'controllers':
                $this->addController($className, $filePath, $modName, $fileType);
                return;
            case 'xarblocks':
                // @todo handle interface? This could be .php, _display.php or /admin.php - last one is not supported
                $this->addBlock($className, $filePath, $modName, $fileType);
                return;
            case 'xarproperties':
                $this->addProperty($className, $filePath, $modName, $fileType);
                return;
            default:
                $subDirs = explode('/', $dirName);
                $dirName = array_shift($subDirs);
                if ($dirName != 'class') {
                    // skip test classes and more subdirs here
                    if (count($subDirs) > 0 || $dirName == 'tests') {
                        // @todo other module subdirs?
                        //$classType = 'others/' . $dirName;
                        return;
                    }
                    // candidate method classes
                    $modType = $dirName;
                    $this->addMethod($className, $filePath, $modName, $modType, $fileType);
                    return;
                }
                $classType = array_shift($subDirs);
                if (empty($classType)) {
                    // regular class files
                    $this->addClassFile($className, $filePath, $modName, $fileType);
                    return;
                }
                if (!in_array($classType, ['eventsubjects', 'eventobservers', 'hooksubjects', 'hookobservers'])) {
                    // @todo other module class subdirs?
                    //$classType = 'classes/' . $classType;
                    return;
                }
                $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                return;
        }
    }

    protected function addClassType(string $classType, string $className, string $filePath, string $modName, string $fileType): void
    {
        $this->classmap[$classType][$modName] ??= [];
        if (!array_key_exists($fileType, $this->classmap[$classType][$modName])) {
            $this->classmap[$classType][$modName][$fileType] = [$className => $filePath];
            return;
        }
        $this->classmap[$classType][$modName][$fileType] = array_merge($this->classmap[$classType][$modName][$fileType], [$className => $filePath]);
    }

    protected function addBlock(string $className, string $filePath, string $modName, string $fileType): void
    {
        if ($this->checkClass) {
            $interface = \iBlockType::class;
            if (!$this->checkInterface($className, $interface)) {
                return;
            }
        }
        // @todo handle interface? This could be .php, _display.php or /admin.php - last one is not supported
        $classType = 'blocks';
        $this->addClassType($classType, $className, $filePath, $modName, $fileType);
    }

    protected function addController(string $className, string $filePath, string $modName, string $fileType): void
    {
        if ($this->checkClass) {
            $interface = \iController::class;
            if ($this->checkInterface($className, $interface)) {
                $classType = 'controllers';
                $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                return;
            }
            $interface = \Xaraya\Routing\RoutesInterface::class;
            if ($this->checkInterface($className, $interface)) {
                $classType = 'routes';
                $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                return;
            }
            // not really used, but see example in dynamicdata
            $interface = \Xaraya\Routing\HandlerInterface::class;
            if ($this->checkInterface($className, $interface)) {
                $classType = 'handlers';
                $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                return;
            }
            /**
            // moved to html/lib/xaraya/bridge/middleware/...
            $interface = \Xaraya\Bridge\Middleware\DefaultRouterInterface::class;
            if ($this->checkInterface($className, $interface)) {
                $classType = 'middleware';
                $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                return;
            }
             */
            return;
        }
        $classType = 'controllers';
        $this->addClassType($classType, $className, $filePath, $modName, $fileType);
    }

    protected function addProperty(string $className, string $filePath, string $modName, string $fileType): void
    {
        // Ignore installer classes of properties (they are extensions)
        if (str_ends_with($className, 'Install')) {
            return;
        }
        if ($this->checkClass) {
            $interface = \iDataProperty::class;
            if (!$this->checkInterface($className, $interface)) {
                return;
            }
        }
        $classType = 'properties';
        $this->addClassType($classType, $className, $filePath, $modName, $fileType);
    }

    protected function addModType(string $className, string $filePath, string $modName, string $fileType): void
    {
        if ($this->checkClass) {
            if ($fileType == 'module') {
                $interface = \Xaraya\Modules\ModuleInterface::class;
            } else {
                $interface = \Xaraya\Modules\ModuleServicesInterface::class;
            }
            if (!$this->checkInterface($className, $interface)) {
                $classType = 'others';
                $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                return;
            }
        }
        $classType = 'modules';
        $this->addClassType($classType, $className, $filePath, $modName, $fileType);
    }

    protected function addTables(string $className, string $filePath, string $modName, string $fileType): void
    {
        if ($this->checkClass) {
            // ...
        }
        $classType = 'tables';
        $this->addClassType($classType, $className, $filePath, $modName, $fileType);
    }

    protected function addVersion(string $className, string $filePath, string $modName, string $fileType): void
    {
        if ($this->checkClass) {
            // ...
        }
        $classType = 'versions';
        $this->addClassType($classType, $className, $filePath, $modName, $fileType);
    }

    protected function addMethod(string $className, string $filePath, string $modName, string $modType, string $fileType): void
    {
        if ($this->checkClass) {
            $interface = \Xaraya\Modules\MethodServicesInterface::class;
            if (!$this->checkInterface($className, $interface)) {
                // @todo put in others here?
                $classType = 'others/' . $modType;
                $this->classmap[$classType] ??= [];
                $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                return;
            }
        }
        // @todo this excludes any methods inside the module class itself
        $classType = 'methods';
        // multi-level modType fileType here
        $this->classmap[$classType][$modName] ??= [];
        $this->classmap[$classType][$modName][$modType] ??= [];
        $this->classmap[$classType][$modName][$modType][$fileType] = [$className => $filePath];
    }

    protected function addClassFile(string $className, string $filePath, string $modName, string $fileType): void
    {
        if ($this->checkClass) {
            // @todo memory issue when trying to check all dataobject classes!? - not reliable here
            $contents = file_get_contents($filePath);
            if (str_contains($contents, ' extends DataObject')) {
                unset($contents);
                $classType = 'dataobjects';
                $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                return;
            }
            unset($contents);
            //$interface = \iDataObject::class;
            //if ($this->checkInterface($className, $interface)) {
            //    $classType = 'dataobjects';
            //    $this->addClassType($classType, $className, $filePath, $modName, $fileType);
            //    return;
            //}
            //$interface = \iDataObjectList::class;
            //if ($this->checkInterface($className, $interface)) {
            //    $classType = 'dataobjects';
            //    $this->addClassType($classType, $className, $filePath, $modName, $fileType);
            //    return;
            //}
        }
        $classType = 'classes';
        $this->addClassType($classType, $className, $filePath, $modName, $fileType);
    }

    protected function checkInterface(string $className, string $interface): bool
    {
        try {
            return is_subclass_of($className, $interface, true);
        } catch (Throwable $e) {
            echo __METHOD__ . ': ' . $e->getMessage() . " for $className<br/>\n";
            return false;
        }
    }
}
