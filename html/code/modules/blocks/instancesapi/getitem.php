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
use EmptyParameterException;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * blocks instancesapi getitem function
 * @extends MethodClass<InstancesApi>
 */
class GetitemMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Fetches an item from the API
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return bool|array Returns false on failure data array on success
     * @throws \EmptyParameterException
     * @see InstancesApi::getitem()
     */
    public function __invoke(array $args = [])
    {
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        if (empty($args)) {
            $msg = 'Missing arguments for #(1) module #(2) function #(3)()';
            $vars = ['blocks', 'instancesapi', 'getitem'];
            throw new EmptyParameterException($vars, $msg);
        }

        $types = $instancesapi->getitems($args);

        if (empty($types)) {
            return false;
        } elseif (count($types) > 1) {
            $msg = 'Missing arguments for #(1) module #(2) function #(3)()';
            $vars = ['blocks', 'instancesapi', 'getitem'];
            throw new EmptyParameterException($vars, $msg);
        } else {
            return reset($types);
        }
    }
}
