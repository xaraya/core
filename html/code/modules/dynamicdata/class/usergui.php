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

use Xaraya\DataObject\Traits\UserGuiInterface;
use Xaraya\DataObject\Traits\UserGuiTrait;
use sys;

sys::import('modules.dynamicdata.class.traits.usergui');

/**
 * Handle (traditional) DD user gui functions via module class
 * Note: this does not replace the object-centric UI handlers or direct use of object methods
 */
class UserGui implements UserGuiInterface
{
    /** @use UserGuiTrait<Module> */
    use UserGuiTrait;
}
