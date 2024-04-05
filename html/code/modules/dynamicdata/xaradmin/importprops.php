<?php
/**
 * Import the dynamic properties
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
 * Import the dynamic properties for a module + itemtype from a static table
 * @todo use context
 */
function dynamicdata_admin_importprops(array $args = [], $context = null)
{
    // Security
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    if(!xarVar::fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
        return;
    }

    if (empty($module_id)) {
        throw new EmptyParameterException('module_id');
    }

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!xarSec::confirmAuthKey()) {
        return xarController::badRequest('bad_author', $context);
    }

    if (!xarMod::apiFunc(
        'dynamicdata',
        'util',
        'importproperties',
        ['module_id' => $module_id,
        'itemtype' => $itemtype,
        'table' => $table,
        'objectid' => $objectid]
    )) {
        return;
    }

    xarController::redirect(xarController::URL(
        'dynamicdata',
        'admin',
        'modifyprop',
        ['module_id' => $module_id,
        'itemtype' => $itemtype]
    ));
}
