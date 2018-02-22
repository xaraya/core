<?php
/**
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
 * Update a theme
 *
 * @author Marty Vance 
 * @param id $ the theme's registered id
 * @param newdisplayname $ the new display name
 * @param newdescription $ the new description
 * @return boolean true on success, false on failure
 */
function themes_admin_update()
{ 
    // Security
    if (!xarSecurityCheck('EditThemes')) return; 
    
    // Get parameters
    if (!xarVarFetch('id', 'int:1:', $regId, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($regId)) return xarResponse::notFound();

    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    $themeInfo = xarThemeGetInfo($regId);

    $themename = $themeInfo['name'];
    $themevars = xarTheme_getVarsByTheme($themename);

    $updatevars = array();
    $delvars = array(); 
    // build array of updated and to-be-deleted theme vars
    foreach($themevars as $themevar) {
        if (!xarVarFetch($themevar['name'], 'isset', $varname)) {return;}

        if (!xarVarFetch($themevar['name'] . '-del', 'isset', $delvar, NULL, XARVAR_NOT_REQUIRED)) {return;}

        if ($delvar == 'delete' && $themevar['prime'] != 1) {
            $delvars[] = $themevar['name'];
        } else {
            if ($varname != $themevar['value']) {
                $uvar = array();
                $uvar['name'] = $themevar['name'];
                $uvar['value'] = $varname;
                if ($themevar['prime'] == 1) {
                    $uvar['prime'] = 1;
                } else {
                    $uvar['prime'] = 0;
                } 
                $uvar['description'] = $themevar['description'];
                $updatevars[] = $uvar;
            } 
        } 
    } 

    if (!xarVarFetch('newvarname',        'str', $newname, '',   XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('newvarvalue',       'str', $newval,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('newvardescription', 'str', $newdesc, NULL, XARVAR_NOT_REQUIRED)) {return;}

    $newname = trim($newname);
    $newname = preg_replace("/[\s]+/", "_", $newname);
    $newname = preg_replace("/\W/", "", $newname);
    $newname = preg_replace("/^(\d)/", "_$1", $newname);

    if (isset($newname) && $newname != '') {
        $updatevars[] = array('name' => $newname,
            'value' => $newval,
            'prime' => 0,
            'description' => $newdesc);
    } 

    if (count($updatevars > 0)) {
        $updated = xarMod::apiFunc('themes',
            'admin',
            'update',
            array('regid' => $regId,
                'updatevars' => $updatevars));
        if (!isset($updated)) {
            $msg = xarML('Unable to update theme variable #(1)', $themevar['name']);
            throw new Exception($msg);
        } 
    } 
    foreach($delvars as $d) {
        $deleted = xarThemeDelVar($themename, $d);
        if (!isset($deleted)) {
            $msg = xarML('Unable to delete theme variable #(1)', $d);
            throw new Exception($msg);
        } 
    } 

    if (!xarVarFetch('return', 'checkbox', $return,  false, XARVAR_NOT_REQUIRED)) {return;}

    if ($return) {
        xarController::redirect(xarModURL('themes', 'admin', 'modify', array('id' => $regId)));
    } else {
        xarController::redirect(xarModURL('themes', 'admin', 'list'));
    } 
    return true;
} 

?>
