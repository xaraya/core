<?php
/**
 * Execute a function in a standalone property
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 * @param array<string, mixed> $args
 * @return string|bool|void
 */
function dynamicdata_user_property(array $args = [])
{
    if (!xarVar::fetch('prop', 'str', $property, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('act', 'str', $act, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (empty($property) || empty($act)) {
        return xarResponse::NotFound();
    }

    try {
        sys::import('properties.' . $property . '.' . $act);
        $function = $property . "_" . $act;
        $function();
        return true;
    } catch (Exception $e) {
        if(xarModVars::get('dynamicdata', 'debugmode') && in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            echo "<pre>";
            print($e->__toString());
        } else {
            return xarResponse::NotFound();
        }
    }
}
