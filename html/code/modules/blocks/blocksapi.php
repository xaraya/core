<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks;

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the modules blocks API
 *
 * @method mixed getinfo(array $args = []) Get blocks API info
 * @method mixed getobject(array $args = []) Gets an object from the blocks API
 * @extends UserApiClass<Module>
 */
class BlocksApi extends UserApiClass
{
    use OtherApiTrait;
    // ...
}
