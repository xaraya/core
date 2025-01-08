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

use Xaraya\Modules\AdminGuiInterface as CoreGuiInterface;
use Xaraya\Modules\AdminGuiTrait as CoreGuiTrait;
use sys;

sys::import('xaraya.modules.adminguitrait');

/**
 * For documentation purposes only - available via AdminGuiTrait
 */
interface AdminGuiInterface extends CoreGuiInterface
{
    // ...
}

/**
 * Trait to handle generic admin gui functions for modules with their own DD objects
 *
 * Example:
 * ```
 * use Xaraya\DataObject\Traits\AdminGuiInterface;
 * use Xaraya\DataObject\Traits\AdminGuiTrait;
 * use sys;
 *
 * sys::import('modules.dynamicdata.class.traits.admingui');
 *
 * class MyClassGui implements AdminGuiInterface
 * {
 *     use AdminGuiTrait;
 * }
 * ```
 */
trait AdminGuiTrait
{
    use CoreGuiTrait;
}
