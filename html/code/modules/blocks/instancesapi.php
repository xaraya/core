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

/**
 * Handle the blocks instances API
 *
 * @method mixed countitems(array $args = []) Returns item count
 * @method mixed createitem(array $args = []) Create an item
 * @method mixed deleteitem(array $args = []) Deletes an item from the API
 * @method mixed getitem(array $args = []) Fetches an item from the API
 * @method mixed getitems(array $args = []) Fetches items from API
 * @method mixed getstates(array $args = []) Fetched block state array
 * @method mixed updateitem(array $args = []) Updates an item in the API
 * @extends UserApiClass<Module>
 */
class InstancesApi extends UserApiClass
{
    use OtherApiTrait;

    public function configure()
    {
        $this->setModType('instances');
        // don't call xar::mod()->apiLoad() for blocks instances API
        // make sure block service is initialized with loadDbInfo() for XarayaCompiler
        $this->block()->init();
    }
}
