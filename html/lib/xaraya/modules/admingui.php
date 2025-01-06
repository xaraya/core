<?php

/**
 * Handle module admin gui functions
 *
 * Usage:
 * ```
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\AdminGuiInterface;
 * use Xaraya\Modules\AdminGuiTrait;
 *
 * class AdminGui implements AdminGuiInterface
 * {
 *     use AdminGuiTrait;
 * }
 * ```
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use sys;

sys::import('xaraya.modules.adminguitrait');

/**
 * Summary of AdminGui
 */
class AdminGui implements AdminGuiInterface
{
    use AdminGuiTrait;
}
