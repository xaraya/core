<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.5.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject;

use Xaraya\DataObject\Traits\AdminApiInterface;
use Xaraya\DataObject\Traits\AdminApiTrait;
use sys;

sys::import('modules.dynamicdata.class.traits.adminapi');

/**
 * Handle (traditional) DD admin api functions via module class
 * Note: this does not replace the direct use of object methods
 */
class AdminApi implements AdminApiInterface
{
    /** @use AdminApiTrait<Module> */
    use AdminApiTrait;
}
