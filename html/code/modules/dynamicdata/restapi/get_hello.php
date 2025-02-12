<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\RestApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\RestApi;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata restapi get_hello function
 * @extends MethodClass<RestApi>
 */
class GetHelloMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Sample REST API call supported by this module (if any)
     * @param array<string,mixed> $args
     * @return string of info
     * @see RestApi::getHello()
     */
    public function __invoke($args = [])
    {
        // @checkme pass all args from handler here?
        //extract($args);
        $result = 'World';
        //$this->var()->find('name', $name);
        return !empty($args['name']) ? $args['name'] : $result;
    }
}
