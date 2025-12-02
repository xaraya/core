<?php

/**
 * Modules Service Helper for Module Alias
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
use VariableNotFoundException;

/**
 * Modules Service Helper for Module Alias
 * @todo evaluate dependency consequences
 * @todo evaluate usage in modules, it's not very common, as in, perhaps worth to scrap and bolt onto a request mapper
 */
class AliasHelper extends ServiceClass
{
    public const SLICE = 'modules.alias';

    /**
     * Resolve module alias
     * @param string $name
     * @return string
     */
    public function resolve(string $alias): string
    {
        if ($alias == 'object') {
            return $alias;
        }
        $xar = $this->getParent();
        try {
            $aliasesMap = $xar->config()->getVar('System.ModuleAliases');
        } catch (VariableNotFoundException) {
            // ignore resolve issues for xarRequest->setURL(['module' => '...']) in ModuleHandler->callHandler()
            $aliasesMap = [];
        }
        return (!empty($aliasesMap[$alias])) ? $aliasesMap[$alias] : $alias;
    }

    public function define($alias, $modName): mixed
    {
        $mod = $this->getParent()->mod();
        if (!$mod->apiLoad('modules', 'admin')) {
            return null;
        }
        $args = ['modName' => $modName, 'aliasModName' => $alias];
        return $mod->apiFunc('modules', 'admin', 'add_module_alias', $args);
    }

    public function remove($alias, $modName): mixed
    {
        $mod = $this->getParent()->mod();
        if (!$mod->apiLoad('modules', 'admin')) {
            return null;
        }
        $args = ['modName' => $modName, 'aliasModName' => $alias];
        return $mod->apiFunc('modules', 'admin', 'delete_module_alias', $args);
    }
}
