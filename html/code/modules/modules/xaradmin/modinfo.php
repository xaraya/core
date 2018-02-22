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
 * @return array data for the template display
 * @todo some facelift
 */
function modules_admin_modinfo()
{
    // Security
    if (!xarSecurityCheck('ViewModules')) return; 
    
    $data = array();
    
    if (!xarVarFetch('id', 'notempty', $id)) {return;}

    // obtain maximum information about module
    $modinfo = xarMod::getInfo($id);

    // data vars for template
    $data['modid']              = xarVarPrepForDisplay($id);
    $data['modname']            = xarVarPrepForDisplay($modinfo['name']);
    $data['moddescr']           = xarVarPrepForDisplay($modinfo['description']);
    $data['moddispname']        = xarVarPrepForDisplay($modinfo['displayname']);
    $data['moddispdesc']        = xarVarPrepForDisplay($modinfo['displaydescription']);
    $data['modlisturl']         = xarModURL('modules', 'admin', 'list');

    $aliasesMap = xarConfigVars::get(null,'System.ModuleAliases');
    $aliases = array();
    foreach ($aliasesMap as $key => $value) {
        if ($value == $data['modname']) $aliases[] = $key;
    }
    $data['aliases']            = !empty($aliases) ? implode(', ', $aliases) : xarML('None');
    $data['moddir']             = sys::code(). 'modules/' . xarVarPrepForDisplay($modinfo['directory']);
    $data['modclass']           = xarVarPrepForDisplay($modinfo['class']);
    $data['modcat']             = xarVarPrepForDisplay($modinfo['category']);
    $data['modver']             = xarVarPrepForDisplay($modinfo['version']);
    $data['modauthor']          = xarVarPrepForDisplay($modinfo['author']);
    $data['modcontact']         = xarVarPrepForDisplay($modinfo['contact']);
    if(!empty($modinfo['dependencyinfo'])){

        $dependencies = array();
        foreach ($modinfo['dependencyinfo'] as $key => $value) {
            if ($key != 0) {
                $data['link'] = xarModURL('modules','admin','modinfo', array('id' => $key));
                $dependencies[] = '<a href="'.$data["link"].'">'.$value['name'].'</a>';
            } else {
                $dependencies[] = $value['name'];
            }
            $data['moddependencies'] = implode(', ', $dependencies);
        }
    } else {
        $data['moddependencies']             = xarML('None');
    }
    
    $modname = $modinfo['name'];
/*    $subjects = array();
    $observers = xarEvents::getObserverModules();
    $hookobservers = xarHooks::getObserverModules($modname);
    //$hooksubjects = xarHooks::getSubjectModules();
    
    if (!empty($hookobservers[$modname]['hooks'])) {
        $data['hookobservers'] = $hookobservers[$modname]['hooks'];
    }
        */
    return $data;
}

?>
