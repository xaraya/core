<?php
/**
 * Delete a table field
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
    
    function dynamicdata_admin_delete_static()
    {
        //Security
        if (!xarSecurity::check('AdminDynamicData')) return;

        $data = [];
        if (!xarVar::fetch('table',      'str:1',  $data['table'],    '',     xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('field' ,     'str:1',  $data['field'] , '' ,          xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('confirm',    'bool',   $data['confirm'], false,       xarVar::NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'dynamicdata_tablefields'));

        $data['tplmodule'] = 'dynamicdata';
        $data['authid'] = xarSec::genAuthKey('dynamicdata');

        if ($data['confirm']) {
        
            // Check for a valid confirmation key
//            if(!xarSec::confirmAuthKey()) return;

            $query = 'ALTER TABLE ' .$data['table'] . ' DROP COLUMN ' . $data['field'];
            $dbconn = xarDB::getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            xarController::redirect(xarController::URL('dynamicdata','admin','view_static',array('table' => $data['table'])));
            return true;
        }
        return $data;
    }
