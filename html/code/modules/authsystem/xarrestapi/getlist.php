<?php
/**
 * @package modules\authsystem
 * @subpackage authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Get the list of REST API calls supported by this module (if any)
 *
 * @return array<mixed> of info
 */
function authsystem_restapi_getlist($args = [])
{
    $apilist = [];
    // $func name as used in xarMod::apiFunc($module, $type, $func, $args)
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
