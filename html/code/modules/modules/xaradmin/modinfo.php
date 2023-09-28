<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * View complete module information/details
 * function passes the data to the template
 * opens in new window when browser is javascript enabled
 * @author Xaraya Development Team
 * @access public
 * @return array<mixed>|void data for the template display
 * @todo some facelift
 */
function modules_admin_modinfo()
{
    // Security
    if (!xarSecurity::check('ViewModules')) return; 
    
    $data = array();
    
    if (!xarVar::fetch('id', 'notempty', $id)) {return;}

    // obtain maximum information about module
    $modinfo = xarMod::getInfo($id);

    // data vars for template
    $data['modid']              = xarVar::prepForDisplay($id);
    $data['modname']            = xarVar::prepForDisplay($modinfo['name']);
    $data['moddescr']           = xarVar::prepForDisplay($modinfo['description']);
    $data['moddispname']        = xarVar::prepForDisplay($modinfo['displayname']);
    $data['moddispdesc']        = xarVar::prepForDisplay($modinfo['displaydescription']);
    $data['modlisturl']         = xarController::URL('modules', 'admin', 'list');

    $aliasesMap = xarConfigVars::get(null,'System.ModuleAliases');
    $aliases = array();
    foreach ($aliasesMap as $key => $value) {
        if ($value == $data['modname']) $aliases[] = $key;
    }
    $data['aliases']            = !empty($aliases) ? implode(', ', $aliases) : xarML('None');
    $data['moddir']             = sys::code(). 'modules/' . xarVar::prepForDisplay($modinfo['directory']);
    $data['modclass']           = xarVar::prepForDisplay($modinfo['class']);
    $data['modcat']             = xarVar::prepForDisplay($modinfo['category']);
    $data['modver']             = xarVar::prepForDisplay($modinfo['version']);
    $data['modauthor']          = xarVar::prepForDisplay($modinfo['author']);
    $data['modcontact']         = xarVar::prepForDisplay($modinfo['contact']);
    if(!empty($modinfo['dependencyinfo'])){

        $dependencies = array();
        foreach ($modinfo['dependencyinfo'] as $key => $value) {
            if ($key != 0) {
                $data['link'] = xarController::URL('modules','admin','modinfo', array('id' => $key));
                $dependencies[] = '<a href="'.$data["link"].'">'.$value['name'].'</a>';
            } else {
                $dependencies[] = $value['name'];
            }
            $data['moddependencies'] = implode(', ', $dependencies);
        }
    } else {
        $data['moddependencies']             = xarML('None');
    }
    
    $data['namespace'] = $modinfo['namespace'] ?? '';
    $modname = $modinfo['name'];
    $hookobservers = xarHooks::getObserverModules($modname);
    if (!empty($hookobservers[$modname]) && !empty($hookobservers[$modname]['scopes'])) {
        $data['hookobservers'] = $hookobservers[$modname]['scopes'];
    }

    return $data;
}
