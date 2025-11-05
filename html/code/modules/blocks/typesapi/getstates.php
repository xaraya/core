<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\TypesApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\TypesApi;
use ixarBlock;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks typesapi getstates function
 * @extends MethodClass<TypesApi>
 */
class GetstatesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Returns blocks state array
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args
     * @return array Block state array
     * @see TypesApi::getstates()
     */
    public function __invoke(array $args = [])
    {

        return [
            ixarBlock::TYPE_STATE_ACTIVE
                => ['id' => ixarBlock::TYPE_STATE_ACTIVE, 'name' => $this->ml('Active')],
            ixarBlock::TYPE_STATE_MISSING
                => ['id' => ixarBlock::TYPE_STATE_MISSING, 'name' => $this->ml('Missing')],
            ixarBlock::TYPE_STATE_ERROR
                => ['id' => ixarBlock::TYPE_STATE_ERROR, 'name' => $this->ml('Error')],
            ixarBlock::TYPE_STATE_MOD_UNAVAILABLE
                => ['id' => ixarBlock::TYPE_STATE_MOD_UNAVAILABLE, 'name' => $this->ml('Unavailable')],
        ];

    }
}
