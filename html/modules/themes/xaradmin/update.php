<?php

/**
 * Update a theme
 *
 * @param id the theme's registered id
 * @param newdisplayname the new display name
 * @param newdescription the new description
 * @returns bool
 * @return true on success, error message on failure
 */
function themes_admin_update()
{
    // Get parameters
    $regId = xarVarCleanFromInput('id');

    if (!isset($regId)) {
        $msg = xarML('No theme id specified',
                    'themes');
        xarExceptionSet(XAR_USER_EXCEPTION,
                    'MISSING_DATA',
                     new DefaultUserException($msg));
        return;
    }


    if (!xarSecConfirmAuthKey()) return;

    $themeInfo = xarThemeGetInfo($regId);

    $themename = $themeInfo['name'];
    $themevars = xarTheme_getVarsByTheme($themename);

    $updatevars = array();
    $delvars = array();

    // build array of updated and to-be-deleted theme vars
    foreach($themevars as $themevar){
        $varname = xarVarCleanFromInput($themevar['name']);

        if (!isset($varname)) {
            $msg = xarML('Missing theme variable #(1)',
                        $themevar['name']);
            xarExceptionSet(XAR_USER_EXCEPTION,
                        'MISSING_DATA',
                         new DefaultUserException($msg));
            return;
        }

        $delvar = xarVarCleanFromInput($themevar['name']."-del");
        if($delvar == 'delete' && $themevar['prime'] != 1){
            $delvars[] = $themevar['name'];
        }
        else{
            if($varname != $themevar['value']){
                $uvar = array();
                $uvar['name'] = $themevar['name'];
                $uvar['value'] = $varname;
                if($themevar['prime'] == 1){
                    $uvar['prime'] = 1;
                }
                else{
                    $uvar['prime'] = 0;
                }
                $uvar['description'] = $themevar['description'];
                $updatevars[] = $uvar;
            }
        }
    }

    list($newname, $newval, $newdesc) = xarVarCleanFromInput('newvarname',
                                                 'newvarvalue',
                                                 'newvardescription');
    $newname = trim($newname);
    $newname = preg_replace("/[\s]+/", "_", $newname);
    $newname = preg_replace("/\W/", "", $newname);
    $newname = preg_replace("/^(\d)/", "_$1", $newname);

    if(isset($newname) && $newname != ''){
        $updatevars[] = array('name' => $newname,
                              'value' => $newval,
                              'prime' => 0,
                              'description' => $newdesc);
    }

    if(count($updatevars > 0)){
        $updated = xarModAPIFunc('themes',
                            'admin',
                            'update',
                            array('regid' => $regId,
                                  'updatevars' => $updatevars));
        if (!isset($updated)){
            $msg = xarML('Unable to update theme variable #(1)',
                        $themevar['name']);
            xarExceptionSet(XAR_USER_EXCEPTION,
                        'BAD_DATA',
                         new DefaultUserException($msg));
            return;
        }

    }
    foreach($delvars as $d){
        $deleted = xarThemeDelVar($themename, $d);
        if (!isset($deleted)){
            $msg = xarML('Unable to delete theme variable #(1)',
                        $d);
            xarExceptionSet(XAR_USER_EXCEPTION,
                        'BAD_DATA',
                         new DefaultUserException($msg));
            return;
        }
    }

    // Success
    xarSessionSetVar('themes_statusmsg', xarML('Updated Theme Information',
                                        'themes'));
    $return = xarVarCleanFromInput('return');

    if($return == 1){
        xarResponseRedirect(xarModURL('themes', 'admin', 'modify', array('id' => $regId)));
    }
    else{
        xarResponseRedirect(xarModURL('themes', 'admin', 'list'));
    }
    return true;
}

?>