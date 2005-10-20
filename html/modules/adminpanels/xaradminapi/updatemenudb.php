<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * update an adminmenu db entries
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_adminapi_updatemenudb($args)
{
    // no args yet
    extract($args);

    if(!isset($force)) $force = false;

    // what admin mods do we have here?
    $mods = xarModAPIFunc('modules',
                          'admin',
                          'getlist',
                          array('filter'     => array('AdminCapable' => 1)));
    if(empty($mods)) {
        // none, so dont do anything
        // happy return
        return true;
    }else{
        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();
        $menutable = $xartable['admin_menu'];

        // is the number of rows in our table equal to the number of admin modules?
        // we need to compare them name by name
        // since we can only add/activate one module at a time,
        // there is no need for a more sophisticated check

        $query =   "SELECT COUNT(*)
                    FROM $menutable";
        $result =& $dbconn->Execute($query);
        if (!$result) return;

        // Obtain the number of items
        list($num) = $result->fields;

        if($num == count($mods) && !$force){
            // just return
            return true;
        }else{
            // just empty the table
            $query =   "DELETE FROM $menutable";
            $result =& $dbconn->Execute($query);
            if (!$result) return;

            // one modules was added or removed
            // re-populate db table
            foreach($mods as $mod){
                // we want to know the category info for each mod
                $modid = xarModGetIDFromName($mod['name']);
                $modinfo = xarModGetInfo($modid);
                if($modinfo){
                    $modcat = $modinfo['category'];
                }

                $query = "INSERT INTO $menutable (
                          xar_amid, xar_name, xar_category)
                          VALUES (?,?,?)";
                $bindvars = array($dbconn->GenId($menutable),$mod['name'],$modcat);
                $result =& $dbconn->Execute($query,$bindvars);
                if (!$result) return;
            }
        }
    }
    // just return
    return true;
}

?>
