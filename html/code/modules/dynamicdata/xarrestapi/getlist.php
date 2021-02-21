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
        'path' => 'hello',
        'method' => 'get',
        'description' => 'Hello World',
        'parameters' => array('name')
    );
    return $apilist;
}
