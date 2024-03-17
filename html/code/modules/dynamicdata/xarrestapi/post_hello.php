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
 * Sample REST API call supported by this module (if any)
 *
 * @param array<string, mixed> $args
 * @return string of info
 */
function dynamicdata_restapi_post_hello($args = [], $context = null)
{
    // @checkme handle POSTed args by passing $args['input'] only in handler?
    //extract($args);
    $result = 'World';
    //xarVar::fetch('name', 'isset', $name, null, xarVar::NOT_REQUIRED);
    return !empty($args['name']) ? $args['name'] : $result;
}
