<?php
/**
 * Main entry point for the admin interface of this module
 *
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
 * Main entry point for the admin interface of this module
 *
 * This function is the default function for the admin interface, and is called whenever the module is
 * initiated with only an admin type but no func parameter passed.
 * The function displays the module's overview page, or redirects to another page if overviews are disabled.
 *
 * @return mixed output display string or boolean true if redirected
 *
 */
function dynamicdata_admin_main(array $args = [], $context = null)
{
    // Security
    if(!xarSecurity::check('EditDynamicData')) {
        return;
    }

    // @todo use $context here if available
    $samemodule = xarController::isRefererSameModule();

    if (((bool) xarModVars::get('modules', 'disableoverview') == false) || $samemodule) {
        return xarTpl::module('dynamicdata', 'admin', 'overview');
    } else {
        xarController::redirect(xarController::URL('dynamicdata', 'admin', 'view'));
        return true;
    }
}
