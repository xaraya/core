<?php
/**
 * File: $Id$
 *
 * Modify module settings
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * Modify module settings
 *
 * This function queries the database for
 * the module's information and then queries
 * for any hooks that the module could use
 * and passes the data to the template.
 *
 * @param id module id
 * @returns array
 * @return an array of variables to pass to the template
 */
//TODO: Make the phpdoc true :)
function modules_admin_modify()
{
    // xarVarFetch does validation if not explicitly set to be not required
    xarVarFetch('id','id',$regId);
    xarVarFetch('details','str:0:1',$details,0,XARVAR_NOT_REQUIRED);

    $modInfo = xarModGetInfo($regId);
    if (!isset($modInfo)) return;

    $modName     = $modInfo['name'];
    $displayName = $modInfo['displayname'];

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"$modName::$regId")) return;

    $data['savechangeslabel'] = xarML('Save Changes');
    if ($details) {
        $data['DetailsLabel'] = xarML('Hide Details');
        $data['DetailsURL'] = xarModURL('modules','admin','modify',
                                        array('id' => $regId));
    } else {
        $data['DetailsLabel'] = xarML('Show Details');
        $data['DetailsURL'] = xarModURL('modules','admin','modify',
                                        array('id' => $regId, 'details' => true));
    }

    // Get the list of all hook modules, and the current hooks enabled for this module
    $hooklist = xarModAPIFunc('modules','admin','gethooklist',
                              array('modName' => $modName));

    // Get the list of all item types for this module (if any)
    $itemtypes = xarModAPIFunc($modName,'user','getitemtypes',
                               // don't throw an exception if this function doesn't exist
                               array(), 0);
    if (isset($itemtypes)) {
        $data['itemtypes'] = $itemtypes;
    } else {
        $data['itemtypes'] = array();
    }

    // $data[hooklist] is the master array which holds all info
    // about the registered hooks.
    $data['hooklist'] = array();
    
    // Loop over available $key => $value pairs in hooklist
    // $modname is assigned key (name of module)
    // $hooks is assigned object:action:area
    // MrB: removed the details check, it's simpler to have the same datastructure 
    // allways, and I think there's not much of a performance hit.
    // TODO: make the different hooks selectable per type of hook
    foreach ($hooklist as $hookmodname => $hooks) {
        $data['hooklist'][$hookmodname]['modname'] = $hookmodname;
        $data['hooklist'][$hookmodname]['checked'] = array();
        $data['hooklist'][$hookmodname]['hooks'] = array();
        // Fill in the details for the different hooks
        foreach ($hooks as $hook => $modules) {
            if (!empty($modules[$modName])) {
                foreach ($modules[$modName] as $itemType => $val) {
                    $data['hooklist'][$hookmodname]['checked'][$itemType] = 1;
                }
            }
            $data['hooklist'][$hookmodname]['hooks'][$hook] = 1;
        }
    }
  //print_r($data['hooklist']);
    // End form
    $data['details'] = $details;
    $data['authid'] = xarSecGenAuthKey();
    $data['id'] = $regId;
    $data['displayname'] = $modInfo['displayname'];
    return $data;
}

?>
