<?php
/**
 * File: $Id
 *
 * Build adminmenu items sorted in different ways
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2004 The Digital Development Foundation Inc.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
*/
/**
 * build adminmenu items 
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   string menustyle  'bycat'
 * @return  $catdata array on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_adminapi_buildmenu($args)
{
    extract($args);
    if(!isset($menustyle)) $menustyle='bycat';

    // categories according RFC11 & RFC13
    // TODO: store our categories in module variable to be able add/remove them via interface
    // TODO: better yet, link them up to categories module
    switch($menustyle) {
        default:
        case 'bycat':
            $cats = array(  '1'=>'Global',
                    '2'=>'Content',
                    '3'=>'Users & Groups',
                    '4'=>'Miscellaneous');
            break;
        case 'bygroup':
             // sample groups since there are none defined in the module var
            $cats = array(  '1'=>'Essential',
                            '2'=>'Useful',
                            '3'=>'Testing');
            break;
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $menutable = $xartable['admin_menu'];

    $catdata = array();

    foreach($cats as $num=>$cat){
        // get records from the table to match our categories
        $query =   "SELECT xar_name
                    FROM $menutable
                    WHERE xar_category = ?
                    AND xar_flag = 1";
        $result =& $dbconn->Execute($query,array($cat));
        if (!$result) return;

        // the category label
        if($cat == 'Users & Groups') {
            // need xhtml compliant label for display
            // FIXME: this doesnt belong here
            $cat = 'Users &amp; Groups';
        }

        $catdata[$cat] = array();
        // module urls
        while(!$result->EOF){
            list($mname) = $result->fields;
            $result->MoveNext();
            $modinfo = xarModGetInfo(xarModGetIDFromName($mname));
            // new style admin links
            $catdata[$cat][$mname]['displayname'] = $modinfo['displayname'];
        }
    }
    // return the data
    return $catdata;
}

?>