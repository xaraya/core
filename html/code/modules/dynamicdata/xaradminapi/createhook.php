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
 * create fields for an item - hook for ('item','create','API')
 * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        ingeger  $args['objectid'] ID of the object<br/>
 *        string   $args['extrainfo'] extra information
 * @return array<mixed> true on success, false on failure
 */
function dynamicdata_adminapi_createhook(array $args = [])
{
    // we rely on the updatehook to do the real work here
    $args['dd_function'] = 'createhook';
    return xarMod::apiFunc('dynamicdata', 'admin', 'updatehook', $args);
}
