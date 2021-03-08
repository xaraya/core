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
 * @return array of info
 */
function authsystem_restapi_getlist($args = [])
{
    $apilist = [];
    $apilist['honeypot'] = [
        //'type' => 'rest',  // default
        'path' => 'login',
        'method' => 'post',
        //'security' => false,  // default REST APIs are public
        'description' => 'Call REST API honeypot() in module authsystem',
        'requestBody' => ['application/json' => ['username', 'password']],
    ];
    return $apilist;
}
