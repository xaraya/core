<?php

/**
 * Modules Service Helper for Module Alias
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
use xarModAlias;

/**
 * Modules Service Helper for Module Alias
 */
class AliasHelper extends ServiceClass
{
    public const SLICE = 'modules.alias';

    /**
     * Resolve module alias
     * @param string $name
     * @return string
     */
    public function resolveAlias(string $name): string
    {
        return xarModAlias::resolve($name);
    }
}
