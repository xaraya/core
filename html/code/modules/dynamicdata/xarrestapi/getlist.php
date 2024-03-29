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
 * Parameters and requestBody fields can be specified as follows:
 * => ['itemtype', 'itemids']  // list of field names, each defaults to type 'string'
 * => ['itemtype' => 'string', 'itemids' => 'array']  // specify the field type, 'array' defaults to array of 'string'
 * => ['itemtype' => 'string', 'itemids' => ['integer']]  // specify the array items type as 'integer' here
 * => ['itemtype' => ['type' => 'string'], 'itemids' => ['type' => 'array', 'items' => ['type' => 'integer']]]  // rest
 *
 * @param array<string, mixed> $args
 * @return array<mixed> of info
 */
function dynamicdata_restapi_getlist($args = [], $context = null)
{
    $apilist = [];
    // $func name as used in xarMod::apiFunc($module, $type, $func, $args)
    $apilist['get_hello'] = [
        //'module' => 'dynamicdata',  // default = current module
        //'type' => 'rest',  // default = rest, other $type options are user, admin, ... as usual
        //'name' => 'get_hello',  // default = $api array key above
        //'args' => ['extra' => 'more'],  // @checkme allow default args to start with
        'path' => 'hello',  // path to use in REST API operation /modules/{module}/{path}
        //'path' => 'hello/{name}',  // path to use in REST API operation /modules/{module}/{path} with path parameter
        'method' => 'get',  // method to use in REST API operation
        //'security' => false,  // default = false REST APIs are public, if true check for authenticated user
        'description' => 'Call REST API get_hello() in module dynamicdata defined in code/modules/dynamicdata/xarrestapi/get_hello.php',
        'parameters' => ['name'],  // optional query parameter(s)
    ];
    // $func name as used in xarMod::apiFunc($module, $type, $func, $args)
    $apilist['post_hello'] = [
        //'type' => 'rest',  // default = rest, other options are user, admin, ... as usual
        'path' => 'hello',  // path to use in REST API operation /modules/{module}/{path}
        'method' => 'post',  // method to use in REST API operation
        'security' => true,  // default = false REST APIs are public, if true check for authenticated user
        'description' => 'Call REST API post_hello() in module dynamicdata defined in code/modules/dynamicdata/xarrestapi/post_hello.php',
        // @checkme verify/expand how POSTed values are defined - assuming simple json object with string props for now
        'requestBody' => ['application/json' => ['name', 'score']],  // optional requestBody
    ];
    // $func name as used in xarMod::apiFunc($module, $type, $func, $args)
    $apilist['getobjects'] = [
        'type' => 'user',  // default = rest, other options are user, admin, ... as usual
        'path' => 'anotherapi',  // path to use in REST API operation /modules/{module}/{path}
        'method' => 'get',  // method to use in REST API operation
        'security' => 'ReadDynamicDataItem',  // optional security mask depending on the api
        'description' => 'Call existing module userapi function (getobjects) via REST API with optional parameter(s)',
        'parameters' => ['moduleid'],  // optional query parameter(s)
        // @todo transform assoc array("$itemid" => $item) to list of $item or not?
        'response' => ['type' => 'array', 'items' => ['type' => 'object']],  // optional response schema
        //'caching' => false,  // optional disabling of caching e.g. if it overlaps with variable caching already
        //'paging' => false,  // add optional paging parameters
    ];
    // $func name as used in xarMod::apiFunc($module, $type, $func, $args)
    $apilist['export'] = [
        'type' => 'util',  // default = rest, other options are user, admin, ... as usual
        'path' => 'export',  // path to use in REST API operation /modules/{module}/{path}
        'method' => 'get',  // method to use in REST API operation
        'security' => 'AdminDynamicDataItem',  // optional security mask depending on the api
        'description' => 'Call dynamicdata utilapi export function via REST API with optional parameter(s)',
        'parameters' => ['objectid', 'itemid'],  // optional query parameter(s)
        'mediatype' => 'application/xml',  // optional response media type (instead of default application/json)
        'response' => ['type' => 'string'],  // optional response schema
    ];
    return $apilist;
}
