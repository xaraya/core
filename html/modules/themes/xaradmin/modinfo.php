<?php

/**
 * View complete module information/details
 * function passes the data to the template
 *
 * @access public
 * @param none
 * @returns array
 * @todo some facelift
 */
function themes_admin_modinfo(){
    
    // Security check - not needed here, imo 
    // we just show some info here, not changing anything
    if (!xarSecConfirmAuthKey()) return;

    $data = array();
    
    $id = xarVarCleanFromInput('id');
    if (empty($id)) {
        $msg = xarML('No theme id specified', 'themes');
        xarExceptionSet(XAR_USER_EXCEPTION,
                        'MISSING_DATA',
                        new DefaultUserException($msg));
        return;
    }
    // obtain maximum information about module
    $modinfo = xarThemeGetInfo($id);
    
    // data vars for template
    $data['modid']              = xarVarPrepForDisplay($id);
    $data['modname']            = xarVarPrepForDisplay($modinfo['name']);
    $data['moddescr']           = xarVarPrepForDisplay($modinfo['description']);
    //$data['moddispname']        = xarVarPrepForDisplay($modinfo['displayname']);
    $data['modlisturl']         = xarModURL('themes', 'admin', 'list');

    $data['moddir']             = xarVarPrepForDisplay($modinfo['directory']);
    $data['modclass']           = xarVarPrepForDisplay($modinfo['class']);
    $data['modver']             = xarVarPrepForDisplay($modinfo['version']);
    $data['modauthor']          = preg_replace('/,/', '<br />', xarVarPrepForDisplay($modinfo['author']));
    if(!empty($modinfo['dependency'])){
        $dependency             = xarMLByKey('Working on it...');
    } else {
        $dependency             = xarMLByKey('None');
    }
    $data['moddependency']      = xarVarPrepForDisplay($dependency);
    
    // Redirect
    return $data;
}

?>