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
 * delete fields for an item - hook for ('item','delete','API')
 *
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['objectid'] ID of the object<br/>
 *        string   $args['extrainfo'] extra information
 * @return array true on success, false on failure
 * @throws BadParameterException
 */
function dynamicdata_adminapi_deletehook(array $args = [])
{
    extract($args);

    // everything is already validated in HookSubject, except possible empty objectid/itemid for create/display
    $modname = $extrainfo['module'];
    $itemtype = $extrainfo['itemtype'];
    $itemid = $extrainfo['itemid'];
    $module_id = $extrainfo['module_id'];

    // don't allow hooking to yourself in DD
    if ($modname == 'dynamicdata') {
        return $extrainfo;
    }

    if (empty($itemid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = ['item id', 'admin', 'deletehook', 'dynamicdata'];
        throw new BadParameterException($vars, $msg);
    }

    if (!xarMod::apiFunc(
        'dynamicdata',
        'admin',
        'delete',
        ['module_id'    => $module_id,
              'itemtype' => $itemtype,
              'itemid'   => $itemid]
    )) {
        return $extrainfo;
    }
    return $extrainfo;
}
