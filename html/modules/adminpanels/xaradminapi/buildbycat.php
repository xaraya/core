<?php
/**
 * File: $Id
 *
 * Build adminmenu items sorted by category
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
*/
/**
 * build adminmenu items sorted by category
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  $catdata array on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_adminapi_buildbycat($args){

    // extract($args);
    // we pass no args atm

    // categories according RFC11 & RFC13
    // TODO: store our categories in module variable to be able add/remove them via interface
    $cats = array(  '1'=>'Global',
                    '2'=>'Content',
                    '3'=>'Users & Groups',
                    '4'=>'Miscellaneous');

    list($dbconn) = xarDBGetConn();
    $xartable =& xarDBGetTables();
    $menutable = $xartable['admin_menu'];

    $catdata = array();

    foreach($cats as $num=>$cat){
        // get records from the table to match our categories
        $query =   "SELECT xar_name
                    FROM $menutable
                    WHERE xar_category = '".xarVarPrepForStore($cat)."'
                    AND xar_flag = 1";
        $result =& $dbconn->Execute($query);
        if (!$result) return;

        // the category label
        if($cat == 'Users & Groups') {
            // need xhtml compliant label for display
            $cat = 'Users &amp; Groups';
        }
        
        $catdata[$cat] = array();
        // module urls
        while(!$result->EOF){
            list($mname) = $result->fields;
            $result->MoveNext();
            $modinfo = xarModGetInfo(xarModGetIDFromName($mname));
            // new style admin links
            $catdata[$cat][$modinfo['displayname']] = array();
        }
    }
    // return the data
    return $catdata;
}

