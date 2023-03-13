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
    sys::import('modules.dynamicdata.class.objects.master');
    
    function dynamicdata_admin_delete_static_table()
    {
        // Security
        if (!xarSecurity::check('AdminDynamicData')) return;

        $data = [];
        if (!xarVar::fetch('table',      'str:1',  $data['table'],    '',     xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('confirm',    'bool',   $data['confirm'], false,       xarVar::NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'dynamicdata_tablefields'));

        $data['tplmodule'] = 'dynamicdata';

        if ($data['confirm']) {
        
            $query = 'DROP TABLE ' .$data['table'];
            $dbconn = xarDB::getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            xarController::redirect(xarController::URL('dynamicdata','admin','view_static'));
            return true;
        }
        return $data;
    }
