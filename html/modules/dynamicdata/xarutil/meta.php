<?php
/**
 * File: $Id$
 *
 * Return meta data (test only)
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
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

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $data['tables'] = xarModAPIFunc('dynamicdata','util','getmeta',
                                    array('table' => $table));

    $data['export'] = $export;

    xarTplSetPageTemplateName('admin');

    return $data;
}

?>
