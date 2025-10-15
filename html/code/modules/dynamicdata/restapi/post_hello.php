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
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata restapi post_hello function
 * @extends MethodClass<RestApi>
 */
class PostHelloMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Sample REST API call supported by this module (if any)
     * @param array<string,mixed> $args
     * @return string of info
     * @see RestApi::postHello()
     */
    public function __invoke($args = [])
    {
        // @checkme handle POSTed args by passing $args['input'] only in handler?
        //extract($args);
        $result = 'World';
        //$this->var()->find('name', $name);
        return !empty($args['name']) ? $args['name'] : $result;
    }
}
