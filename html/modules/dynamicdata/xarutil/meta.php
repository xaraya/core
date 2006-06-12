<?php
/**
 * Return meta data 
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Return meta data (test only)
 */
function dynamicdata_util_meta($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if (!xarVarFetch('export', 'notempty', $export, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('table', 'notempty', $table, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('showdb', 'notempty', $showdb, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('db', 'notempty', $db, '', XARVAR_NOT_REQUIRED)) {return;}

    $data = array();

    if (!empty($showdb)) {
        $data['tables'] = array();

        $dbconn =& xarDBGetConn();
        // Note: this only works if we use the same database connection
        $data['databases'] = $dbconn->MetaDatabases();
        if (empty($db)) {
            $db = xarDBGetName();
        }
        $data['db'] = $db;
        if (empty($data['databases'])) {
            $data['databases'] = array($db);
        }
    } else {
        $data['tables'] = xarModAPIFunc('dynamicdata','util','getmeta',
                                        array('db' => $db, 'table' => $table));
    }

    $data['table'] = $table;
    $data['export'] = $export;
    $data['prop'] = xarModAPIFunc('dynamicdata','user','getproperty',array('type' => 'fieldtype', 'name' => 'dummy'));

    if (xarModGetVar('themes','usedashboard')) {
        $admin_tpl = xarModGetVar('themes','dashtemplate');
    }else {
       $admin_tpl='default';
    }
    xarTplSetPageTemplateName($admin_tpl);

    return $data;
}

?>
