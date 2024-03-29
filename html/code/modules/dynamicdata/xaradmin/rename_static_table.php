<?php
/**
 * Delete a table
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
sys::import('modules.dynamicdata.class.objects.factory');

function dynamicdata_admin_rename_static_table()
{
    // Security
    if (!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    $data = ['table' => '', 'newtable' => '', 'confirm' => false];
    if (!xarVar::fetch('table', 'str:1', $data['table'], '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('newtable', 'str:1', $data['newtable'], '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('confirm', 'bool', $data['confirm'], false, xarVar::NOT_REQUIRED)) {
        return;
    }

    $data['object'] = DataObjectFactory::getObject(['name' => 'dynamicdata_tablefields']);

    $data['tplmodule'] = 'dynamicdata';

    if ($data['confirm']) {
        if (empty($data['newtable'])) {
            xarController::redirect(xarController::URL('dynamicdata', 'admin', 'view_static', ['table' => $data['table']]));
        }
        $query = 'RENAME TABLE ' . $data['table'] . ' TO ' . $data['newtable'];
        $dbconn = xarDB::getConn();
        $dbconn->Execute($query);

        // Jump to the next page
        xarController::redirect(xarController::URL('dynamicdata', 'admin', 'view_static', ['table' => $data['newtable']]));
        return true;
    }
    return $data;
}
