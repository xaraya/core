<?php

/**
 * Configure hooks by hook module
 *
 * @param none
 *
 */
function modules_admin_hooks()
{
// Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    $curhook = xarVarCleanFromInput('hook');
    if (!isset($curhook)) {
        $curhook = '';
    }

    // Get the list of all hook modules, and the current hooks enabled for all modules
    $hooklist = xarModAPIFunc('modules','admin','gethooklist');

    $data = array();
    $data['savechangeslabel'] = xarML('Save Changes');
    $data['hookmodules'] = array();
    $data['hookedmodules'] = array();
    $data['curhook'] = '';
    $data['hooktypes'] = array();
    $data['authid'] = '';

    if (!empty($curhook)) {
        // Get list of modules likely to be "interested" in hooks
        //$modList = xarModGetList(array('Category' => 'Content'));
        $modList = xarModGetList(array(),NULL,NULL,'category/name');
        //throw back
        if (!isset($modList)) return;

        $oldcat = '';
        for ($i = 0; $i < count($modList); $i++) {
            $modList[$i]['checked'] = '';
            if ($oldcat != $modList[$i]['category']) {
                $modList[$i]['header'] = $modList[$i]['category'];
                $oldcat = $modList[$i]['category'];
            } else {
                $modList[$i]['header'] = '';
            }
            foreach ($hooklist[$curhook] as $hook => $hookedmods) {
                if (!empty($hookedmods[$modList[$i]['name']])) {
                    $modList[$i]['checked'] = ' checked';
                    break;
                }
            }
        }
        $data['curhook'] = $curhook;
        $data['hookedmodules'] = $modList;
        $data['authid'] = xarSecGenAuthKey();

        $details = xarVarCleanFromInput('details');
        if ($details) {
            $data['DetailsLabel'] = xarML('Hide Details');
            $data['DetailsURL'] = xarModURL('modules','admin','hooks',
                                            array('hook' => $curhook));

            foreach ($hooklist[$curhook] as $hook => $hookedmods) {
                $data['hooktypes'][] = array('hooktype' => $hook);
            }
        } else {
            $data['DetailsLabel'] = xarML('Show Details');
            $data['DetailsURL'] = xarModURL('modules','admin','hooks',
                                            array('hook' => $curhook, 'details' => true));
        }
    }

    foreach ($hooklist as $hookmodname => $hooks) {
        
        // Get module display name
        $regid = xarModGetIDFromName($hookmodname);
        $modinfo = xarModGetInfo($regid);
        $data['hookmodules'][] = array('modid' => $regid,
                                       'modname' => $hookmodname,
                                       'modtitle' => $modinfo['description'],
                                       'modlink' => xarModURL('modules','admin','hooks',
                                                              array('hook' => $hookmodname)));
    }

    //return the output 
    return $data;
}

?>