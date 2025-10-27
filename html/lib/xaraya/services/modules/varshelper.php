<?php

/**
 * Modules Service Helper for Module Variables
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
use xarModVars;

/**
 * Modules Service Helper for Module Variables
 */
class VarsHelper extends ServiceClass
{
    public const SLICE = 'modules.vars';

    public function get(string $modName, string $varName): mixed
    {
        return xarModVars::get($modName, $varName);
    }

    public function set(string $modName, string $varName, mixed $value): bool
    {
        if (is_null($value)) {
            return xarModVars::delete($modName, $varName);
        }
        return xarModVars::set($modName, $varName, $value);
    }

    public function delete(string $modName, string $varName): bool
    {
        return xarModVars::delete($modName, $varName);
    }

    public function cache(string $modName, ?string $source = null): void
    {
        $source ??= __CLASS__ . '::' . __FUNCTION__;
        xarModVars::cache($modName, $source);
    }

    public function getID(string $modName, string $varName): int
    {
        return xarModVars::getID($modName, $varName);
    }

    public function disableOverview(): bool
    {
        return xarModVars::get('modules', 'disableoverview') ? true : false;
    }
}
