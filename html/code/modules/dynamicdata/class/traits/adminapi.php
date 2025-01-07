<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.5.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\Traits;

use Xaraya\Modules\AdminApiInterface as CoreApiInterface;
use Xaraya\Modules\AdminApiTrait as CoreApiTrait;
use sys;

sys::import('xaraya.modules.adminapitrait');

/**
 * For documentation purposes only - available via AdminApiTrait
 */
interface AdminApiInterface extends CoreApiInterface
{
    // ...
}

/**
 * Trait to handle generic admin api functions for modules with their own DD objects
 *
 * Example:
 * ```
 * use Xaraya\DataObject\Traits\AdminApiInterface;
 * use Xaraya\DataObject\Traits\AdminApiTrait;
 * use sys;
 *
 * sys::import('modules.dynamicdata.class.traits.adminapi');
 *
 * class MyClassApi implements AdminApiInterface
 * {
 *     use AdminApiTrait;
 * }
 * ```
 */
trait AdminApiTrait
{
    use CoreApiTrait;
}
