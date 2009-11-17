<?php
/**
 * Delete a table
 *
 */
    sys::import('modules.dynamicdata.class.objects.master');
    
    function dynamicdata_util_delete_static_table()
    {
        if (!xarSecurityCheck('AdminDynamicData')) return;

        if (!xarVarFetch('table',      'str:1',  $data['table'],    '',     XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,       XARVAR_NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'dynamicdata_tablefields'));

        $data['tplmodule'] = 'dynamicdata';

        if ($data['confirm']) {
        
            $query = 'DROP TABLE ' .$data['table'];
            $dbconn = xarDB::getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            xarResponse::redirect(xarModURL('dynamicdata','util','view_static'));
            return true;
        }
        return $data;
    }

?>