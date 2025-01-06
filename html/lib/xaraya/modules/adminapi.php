<?php

/**
 * Handle module admin api functions
 *
 * Usage:
 * ```
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\AdminApiInterface;
 * use Xaraya\Modules\AdminApiTrait;
 *
 * class AdminApi implements AdminApiInterface
 * {
 *     use AdminApiTrait;
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

sys::import('xaraya.modules.adminapitrait');

/**
 * Summary of AdminApi
 */
class AdminApi implements AdminApiInterface
{
    use AdminApiTrait;
}
