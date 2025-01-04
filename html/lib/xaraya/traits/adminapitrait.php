<?php

/**
 * Trait to handle admin api functions
 *
 * Usage:
 * ```
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Core\Traits\AdminApiInterface;
 * use Xaraya\Core\Traits\AdminApiTrait;
 *
 * class AdminApi implements AdminApiInterface
 * {
 *     use AdminApiTrait;
 * }
 * ```
 *
 * @package core\traits
 * @subpackage traits
 * @category Xaraya Web Applications Framework
 * @version 2.5.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Core\Traits;

/**
 * For documentation purposes only - available via AdminApiTrait
 */
interface AdminApiInterface extends UserApiInterface
{
    // ...
}

/**
 * Trait to handle admin api functions
 */
trait AdminApiTrait
{
    use UserApiTrait;
}
