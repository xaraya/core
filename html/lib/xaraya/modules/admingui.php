<?php

/**
 * Handle module admin gui functions
 *
 * Usage:
 * ```
 * # class/admingui.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\AdminGuiClass;
 *
 * class AdminGui extends AdminGuiClass
 * {
 *     public function main($args = []) {
 *         // get main admin overview
 *         // $context = $this->getContext();
 *         return $output;
 *     }
 * }
 * ```
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

sys::import('xaraya.modules.adminguitrait');

/**
 * Handle module admin gui functions
 */
class AdminGuiClass implements AdminGuiInterface
{
    use AdminGuiTrait;
}
