<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\InstancesApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\InstancesApi;
use xarBlock;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks instancesapi getstates function
 * @extends MethodClass<InstancesApi>
 */
class GetstatesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Fetched block state array
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args
     * @return array Returns block state array
     * @see InstancesApi::getstates()
     */
    public function __invoke(array $args = [])
    {
        return [
            xarBlock::BLOCK_STATE_INACTIVE =>
                ['id' => xarBlock::BLOCK_STATE_INACTIVE, 'name' => xarML('Inactive')],
            xarBlock::BLOCK_STATE_HIDDEN =>
                ['id' => xarBlock::BLOCK_STATE_HIDDEN, 'name' => xarML('Hidden')],
            xarBlock::BLOCK_STATE_VISIBLE =>
                ['id' => xarBlock::BLOCK_STATE_VISIBLE, 'name' => xarML('Visible')],
        ];
    }
}
