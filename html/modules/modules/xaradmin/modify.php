<?php

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
    xarVarFetch('details','int:0:1',$details,0,XARVAR_NOT_REQUIRED);

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
        $data['hooklist'][$hookmodname]['checked'] = 0;
        $data['hooklist'][$hookmodname]['hooks'] = array();
        // Fill in the details for the different hooks
        foreach ($hooks as $hook => $modules) {
            if (!empty($modules[$modName])) {
                $checked = 1;
                $data['hooklist'][$hookmodname]['checked'] = 1;
            } else {
                $checked = 0;
            }
            $data['hooklist'][$hookmodname]['hooks'][$hook] = array('hook' => $hook,
                                                                    'value' => 1,
                                                                    'checked' =>$checked);
        }
    }
    if(count($data['hooklist']) == 0){
        $data['nohooks'] = 1;
    }
    else{
        $data['nohooks'] = 0;
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
