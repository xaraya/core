<?php
/**
 * @package modules\blocks
 * @subpackage blocks
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
 * @return array<mixed> of info
 */
function blocks_restapi_getlist($args = [])
{
    $apilist = [];
    // $func name as used in xarMod::apiFunc($module, $type, $func, $args)
    $apilist['getitems'] = [
        'type' => 'instances',  // default = rest, other $type options are user, admin, ... as usual
        'path' => 'instances',  // path to use in REST API operation /modules/{module}/{path} with path parameter
        'method' => 'get',  // method to use in REST API operation
        //'security' => false,  // default = false REST APIs are public, if true check for authenticated user
        'description' => 'Call instances api function getitems() in module blocks',
        'parameters' => ['type', 'module', 'type_category'],  // optional parameter(s)
    ];
    // $func name as used in xarMod::apiFunc($module, $type, $func, $args)
    $apilist['getinfo'] = [
        'type' => 'blocks',  // default = rest, other $type options are user, admin, ... as usual
        'path' => 'instances/{instance}',  // path to use in REST API operation /modules/{module}/{path} with path parameter
        'method' => 'get',  // method to use in REST API operation
        //'security' => false,  // default = false REST APIs are public, if true check for authenticated user
        'description' => 'Call blocks api function getinfo() in module blocks',
        'parameters' => ['state', 'type_state'],  // optional parameter(s)
    ];
    // $func name as used in xarMod::apiFunc($module, $type, $func, $args)
    $apilist['render'] = [
        //'type' => 'rest',  // default = rest, other $type options are user, admin, ... as usual
        'path' => 'render/{instance}',  // path to use in REST API operation /modules/{module}/{path} with path parameter
        'method' => 'get',  // method to use in REST API operation
        'security' => 'ViewBlocks',  // default = false REST APIs are public, if true check for authenticated user
        'description' => 'Call REST API render() in module blocks defined in code/modules/blocks/xarrestapi/render.php',
        //'parameters' => ['name'],  // optional parameter(s)
        'mediatype' => 'text/html',  // optional response media type (instead of default application/json)
        'response' => ['type' => 'string'],  // optional response schema
        'caching' => false,  // @checkme this might overlap with block output caching
    ];
    return $apilist;
}
