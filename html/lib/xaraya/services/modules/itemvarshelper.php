<?php

/**
 * Modules Service Helper for Module Item Variables
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

use Xaraya\Services\ServiceClass;
use xarModItemVars;

/**
 * Modules Service Helper for Module Item Variables
 */
class ItemVarsHelper extends ServiceClass
{
    public const SLICE = 'modules.item';

    public function get(string $modName, string $varName, mixed $itemid = null): mixed
    {
        return xarModItemVars::get($modName, $varName, $itemid);
    }

    public function set(string $modName, string $varName, mixed $value, mixed $itemid = null): bool
    {
        return xarModItemVars::set($modName, $varName, $value, $itemid);
    }

    public function delete(string $modName, string $varName, mixed $itemid = null): bool
    {
        return xarModItemVars::delete($modName, $varName, $itemid);
    }
}
