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
use BadParameterException;
use EmptyParameterException;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks typesapi getitem function
 * @extends MethodClass<TypesApi>
 */
class GetitemMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Fetches item from the API
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return bool|array Returns item on success or false on failure
     * @throws \EmptyParameterException
     * @throws \BadParameterException
     * @see TypesApi::getitem()
     */
    public function __invoke(array $args = [])
    {
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        if (empty($args)) {
            $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
            $vars = ['arguments', 'blocks', 'typesapi', 'getitem'];
            throw new EmptyParameterException($vars, $msg);
        }

        $types = $typesapi->getitems($args);

        if (empty($types)) {
            return false;
        } elseif (count($types) > 1) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = ['arguments', 'blocks', 'typesapi', 'getitem'];
            throw new BadParameterException($vars, $msg);
        } else {
            return reset($types);
        }
    }
}
