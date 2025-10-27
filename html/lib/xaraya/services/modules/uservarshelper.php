<?php

/**
 * Modules Service Helper for Module User Variables
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
use xarModUserVars;

/**
 * Modules Service Helper for Module User Variables
 */
class UserVarsHelper extends ServiceClass
{
    public const SLICE = 'modules.user';

    public function get(string $modName, string $varName, ?int $userId = null): mixed
    {
        return xarModUserVars::get($modName, $varName, $userId);
    }

    public function set(string $modName, string $varName, mixed $value, ?int $userId = null): bool
    {
        return xarModUserVars::set($modName, $varName, $value, $userId);
    }

    public function delete(string $modName, string $varName, ?int $userId = null): bool
    {
        return xarModUserVars::delete($modName, $varName, $userId);
    }
}
