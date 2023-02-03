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
    if (!xarSecurity::check('EditThemes')) return; 
    
    // Get parameters
    if (!xarVar::fetch('id', 'int:1:', $regId, 0, xarVar::NOT_REQUIRED)) return;
    if (empty($regId)) return xarResponse::notFound();

    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    $themeInfo = xarTheme::getInfo($regId);

    $themename = $themeInfo['name'];
    $themevars = xarTheme::getVarsByTheme($themename);

    $updatevars = array();
    $delvars = array(); 
    // build array of updated and to-be-deleted theme vars
    foreach($themevars as $themevar) {
        if (!xarVar::fetch($themevar['name'], 'isset', $varname)) {return;}

        if (!xarVar::fetch($themevar['name'] . '-del', 'isset', $delvar, NULL, xarVar::NOT_REQUIRED)) {return;}

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

    if (!xarVar::fetch('newvarname',        'str', $newname, '',   xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('newvarvalue',       'str', $newval,  NULL, xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('newvardescription', 'str', $newdesc, NULL, xarVar::NOT_REQUIRED)) {return;}

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

    if (count($updatevars) > 0) {
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

    if (!xarVar::fetch('return', 'checkbox', $return,  false, xarVar::NOT_REQUIRED)) {return;}

    if ($return) {
        xarController::redirect(xarController::URL('themes', 'admin', 'modify', array('id' => $regId)));
    } else {
        xarController::redirect(xarController::URL('themes', 'admin', 'view'));
    } 
    return true;
} 

?>
