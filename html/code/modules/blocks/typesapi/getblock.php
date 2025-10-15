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
use Xaraya\Modules\Blocks\BlocksApi;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks typesapi getblock function
 * @extends MethodClass<TypesApi>
 */
class GetblockMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Gets an object from the api
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return array Data array
     * @see TypesApi::getblock()
     */
    public function __invoke(array $args = [])
    {
        /** @var BlocksApi $blocksapi */
        $blocksapi = $this->blocksapi();
        return $blocksapi->getblock($args);
    }
}
