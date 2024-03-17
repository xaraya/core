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
 * Update configuration parameters of the module
 *
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 *
 * @return boolean|string|void and redirect to view_propertydefs
 */
function dynamicdata_admin_update_propertydefs(array $args = [], $context = null)
{
    extract($args);

    if (!xarVar::fetch('flushPropertyCache', 'isset', $flushPropertyCache, null, xarVar::DONT_SET)) {
        return;
    }

    // Security
    if (!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges', 'user', 'errors', ['layout' => 'bad_author']);
    }

    if (isset($flushPropertyCache) && ($flushPropertyCache == true)) {
        $args['flush'] = 'true';
        if(xarMod::apiFunc('dynamicdata', 'admin', 'importpropertytypes', $args)) {
            xarController::redirect(xarController::URL('dynamicdata', 'admin', 'view_propertydefs'));
            return true;
        } else {
            return 'Unknown error while clearing and reloading Property Definition Cache.';
        }
    }

    xarController::redirect(xarController::URL('dynamicdata', 'admin', 'view_propertydefs'));
    return true;
}
