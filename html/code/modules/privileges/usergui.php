<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Privileges;

use Xaraya\Modules\UserGuiClass;
use sys;

sys::import('xaraya.modules.usergui');
sys::import('modules.modules.userapi');

/**
 * Handle the modules user GUI
 *
 * @method mixed errors(array $args = [])
 * @extends UserGuiClass<Module>
 */
class UserGui extends UserGuiClass
{
    // ...
}
