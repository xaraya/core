<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
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
function dynamicdata_restapi_getlist($args = [])
{
    $apilist = [];
    $apilist['get_hello'] = [
        //'type' => 'rest',  // default
        'path' => 'hello',
        'method' => 'get',
        //'security' => false,  // default REST APIs are public
        'description' => 'Call REST API get_hello() in module dynamicdata',
        'parameters' => ['name'],
    ];
    $apilist['post_hello'] = [
        //'type' => 'rest',  // default
        'path' => 'hello',
        'method' => 'post',
        'security' => true,
        'description' => 'Call REST API post_hello() in module dynamicdata',
        // @checkme verify/expand how POSTed values are defined - assuming simple json object with string props for now
        'requestBody' => ['application/json' => ['name', 'score']],
    ];
    $apilist['getobjects'] = [
        'type' => 'user',
        'path' => 'anotherapi',
        'method' => 'get',
        'security' => 'ReadDynamicDataItem',  // choose depending on the api
        'description' => 'Call existing module userapi function (getobjects) via REST API',
        'parameters' => [],
        // @todo transform assoc array("$itemid" => $item) to list of $item or not?
        'response' => ['type' => 'array', 'items' => ['type' => 'object']],
    ];
    return $apilist;
}
