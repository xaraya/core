<?php

/**
 * Default module class without any components or methods
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use sys;

sys::import('xaraya.modules.moduletrait');

/**
 * Default module class without any components or methods
 */
class DefaultModule implements ModuleInterface
{
    use ModuleTrait;

    /**
     * @see \xarMod::privateLoad()
     */
    public function getClassType(string $modType): string|null
    {
        // no class types available here
        return null;
    }

    /**
     * @see \xarMod::getModuleClassMethod()
     */
    public function getCallableMethod(string $modType, string $funcName, string $callType = 'api'): callable|null
    {
        // no callable methods available here
        return null;
    }
}
