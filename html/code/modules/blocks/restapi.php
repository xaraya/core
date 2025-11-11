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
 * Handle the blocks rest API
 *
 * @method mixed getlist(array $args = []) Get the list of REST API calls supported by this module (if any) - Parameters and requestBody fields can be specified as follows: - => ['itemtype', 'itemids']  // list of field names, each defaults to type 'string' - => ['itemtype' => 'string', 'itemids' => 'array']  // specify the field type, 'array' defaults to array of 'string' - => ['itemtype' => 'string', 'itemids' => ['integer']]  // specify the array items type as 'integer' here - => ['itemtype' => ['type' => 'string'], 'itemids' => ['type' => 'array', 'items' => ['type' => 'integer']]]  // rest
 * @method mixed render(array $args = []) Renders a single block
 * @extends UserApiClass<Module>
 */
class RestApi extends UserApiClass
{
    use OtherApiTrait;

    public function configure()
    {
        $this->setModType('rest');
        // don't call xar::mod()->apiLoad() for blocks rest API
    }
}
