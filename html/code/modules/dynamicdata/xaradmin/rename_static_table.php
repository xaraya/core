<?php
/**
 * Delete a table
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/182.html
 */
    sys::import('modules.dynamicdata.class.objects.master');
    
    function dynamicdata_admin_rename_static_table()
    {
        // Security
        if (!xarSecurityCheck('AdminDynamicData')) return;

        if (!xarVarFetch('table',      'str:1',  $data['table'],    '',     XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('newtable',   'str:1',  $data['newtable'],    '',     XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,       XARVAR_NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'dynamicdata_tablefields'));

        $data['tplmodule'] = 'dynamicdata';

        if ($data['confirm']) {
            if (empty($data['newtable'])) 
                xarController::redirect(xarModURL('dynamicdata','admin','view_static',array('table' => $data['table'])));
            $query = 'RENAME TABLE ' . $data['table'] . ' TO ' . $data['newtable'];
            $dbconn = xarDB::getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            xarController::redirect(xarModURL('dynamicdata','admin','view_static',array('table' => $data['newtable'])));
            return true;
        }
        return $data;
    }

?>
