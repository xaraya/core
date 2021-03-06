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
function dynamicdata_restapi_getlist($args = array())
{
    $apilist = array();
    $apilist['get_hello'] = array(
        //'type' => 'rest',  // default
        'path' => 'hello',
        'method' => 'get',
        'description' => 'Call REST API get_hello() in module dynamicdata',
        'parameters' => array('name')
    );
    $apilist['post_hello'] = array(
        //'type' => 'rest',  // default
        'path' => 'hello',
        'method' => 'post',
        'description' => 'Call REST API post_hello() in module dynamicdata',
        // @checkme verify/expand how POSTed values are defined
        'requestBody' => array('application/json' => array('name'))
    );
    $apilist['getobjects'] = array(
        'type' => 'user',
        'path' => 'anotherapi',
        'method' => 'get',
        'description' => 'Call existing module userapi function (getobjects) via REST API',
        'parameters' => array(),
        // @todo transform assoc array("$itemid" => $item) to list of $item
        'response' => array('type' => 'array', 'items' => array('type' => 'object'))
    );
    return $apilist;
}
