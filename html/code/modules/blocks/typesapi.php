<?php

/**
 * @package modules\blocks
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
 * Handle the blocks types API
 *
 * @method mixed countitems(array $args = []) Counts items in the api
 * @method mixed createitem(array $args = []) Creates an item in the API
 * @method mixed deleteitem(array $args = []) Deletes an item from the API
 * @method mixed getfiles(array $args = []) Get a list of available block types from the file system - Recursively traverses the following paths... - /code/blocks/typename/* - looks for file named typename.php (solo blocks) - /code/modules/modulename/xarblocks/typename/* - looks for file named typename.php (module blocks) - /code/modules/modulename/xarblocks/* - looks for files that don't have an _ (ugly, legacy, deprecated)
 * @method mixed getitem(array $args = []) Fetches item from the API
 * @method mixed getitems(array $args = []) Fetches multiple items from the API
 * @method mixed getblock(array $args = []) Gets an object from the api
 * @method mixed getstates(array $args = []) Returns blocks state array
 * @method mixed refresh(array $args = [])
 * @method mixed updateitem(array $args = []) Update item in API
 * @extends UserApiClass<Module>
 */
class TypesApi extends UserApiClass
{
    use OtherApiTrait;
    // ...
}
