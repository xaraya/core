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
            'others' => [],
        ];
    }

    /**
     * Summary of parse
     * @return array<string, array<mixed>>
     */
    public function parse(string $file): array
    {
        $this->classmap['autoload'] = ['classmap' => $file];
        if (!file_exists($file)) {
            return $this->classmap;
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
                $this->addModType($className, $filePath, $modName, $fileType);
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
                    if (count($subDirs) > 0) {
                        // @todo other module subdirs?
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
                    $classType = 'others';
                    $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                    return;
                }
                if (!in_array($classType, ['eventsubjects', 'eventobservers', 'hooksubjects', 'hookobservers'])) {
                    // @todo other module class subdirs?
                    return;
                }
                $this->addClassType($classType, $className, $filePath, $modName, $fileType);
                return;
        }
    }

    protected function addClassType(string $classType, string $className, string $filePath, string $modName, string $fileType): void
    {
        $this->classmap[$classType][$modName] ??= [];
        $this->classmap[$classType][$modName][$fileType] = [$className => $filePath];
    }

    protected function addBlock(string $className, string $filePath, string $modName, string $fileType): void
    {
        if ($this->checkClass) {
            sys::import('xaraya.structures.containers.blocks.blocktype');
            $interface = \iBlockType::class;
            if (!is_subclass_of($className, $interface, true)) {
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
            sys::import('xaraya.mapper.controllers.interfaces');
            $interface = \iController::class;
            if (!is_subclass_of($className, $interface, true)) {
                return;
            }
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
            sys::import('modules.dynamicdata.class.properties.interfaces');
            $interface = \iDataProperty::class;
            if (!is_subclass_of($className, $interface, true)) {
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
                sys::import('xaraya.modules.moduletrait');
                $interface = \Xaraya\Modules\ModuleInterface::class;
            } else {
                sys::import('xaraya.modules.servicestrait');
                $interface = \Xaraya\Modules\ModuleServicesInterface::class;
            }
            if (!is_subclass_of($className, $interface, true)) {
                return;
            }
        }
        $classType = 'modules';
        $this->addClassType($classType, $className, $filePath, $modName, $fileType);
    }

    protected function addMethod(string $className, string $filePath, string $modName, string $modType, string $fileType): void
    {
        if ($this->checkClass) {
            sys::import('xaraya.modules.method');
            $interface = \Xaraya\Modules\MethodServicesInterface::class;
            if (!is_subclass_of($className, $interface, true)) {
                return;
            }
        }
        $classType = 'methods';
        // multi-level modType fileType here
        $this->classmap[$classType][$modName] ??= [];
        $this->classmap[$classType][$modName][$modType] ??= [];
        $this->classmap[$classType][$modName][$modType][$fileType] = [$className => $filePath];
    }
}
