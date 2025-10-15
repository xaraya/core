<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem\RestApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Authsystem\RestApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem restapi getlist function
 * @extends MethodClass<RestApi>
 */
class GetlistMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get the list of REST API calls supported by this module (if any)
     * @return array of info
     * @see RestApi::getlist()
     */
    public function __invoke(array $args = [])
    {
        $apilist = [];
        // $func name as used in $this->mod()->apiFunc($module, $type, $func, $args)
        $apilist['honeypot'] = [
            //'type' => 'rest',  // default = rest, other $type options are user, admin, ... as usual
            'path' => 'login',  // path to use in REST API operation /modules/{module}/{path}
            'method' => 'post',  // method to use in REST API operation
            //'security' => false,  // default = false REST APIs are public, if true check for authenticated user
            'description' => 'Call REST API honeypot() in module authsystem defined in code/modules/authsystem/xarrestapi/honeypot.php',
            'requestBody' => ['application/json' => ['username', 'password']],  // optional requestBody
        ];
        return $apilist;
    }
}
