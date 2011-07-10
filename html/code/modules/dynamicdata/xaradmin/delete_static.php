<?php
/**
 * Delete a table field
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 */
    sys::import('modules.dynamicdata.class.objects.master');
    
    function dynamicdata_admin_delete_static()
    {
        //Security
        if (!xarSecurityCheck('AdminDynamicData')) return;

        if (!xarVarFetch('table',      'str:1',  $data['table'],    '',     XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('field' ,     'str:1',  $data['field'] , '' ,          XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,       XARVAR_NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'dynamicdata_tablefields'));

        $data['tplmodule'] = 'dynamicdata';
        $data['authid'] = xarSecGenAuthKey('dynamicdata');

        if ($data['confirm']) {
        
            // Check for a valid confirmation key
//            if(!xarSecConfirmAuthKey()) return;

            $query = 'ALTER TABLE ' .$data['table'] . ' DROP COLUMN ' . $data['field'];
            $dbconn = xarDB::getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            xarController::redirect(xarModURL('dynamicdata','admin','view_static',array('table' => $data['table'])));
            return true;
        }
        return $data;
    }

?>
