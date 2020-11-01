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
 * Tools to build and verify modules elements
 *
 * @author Xaraya Development Team
 * @access public
 * @return array data for the template display
 * @todo some facelift
 */
function modules_admin_tools()
{
    // Security
    if (!xarSecurity::check('AdminModules')) return; 
        
    $data = array();
    
/*     if (!xarVar::fetch('id', 'id', $id)) {return;} */
/*     // obtain maximum information about module */
/*     $modinfo = xarMod::getInfo($id); */
/*      */
/*     // data vars for template */
/*     $data['modid']              = xarVar::prepForDisplay($id); */
/*     $data['modname']            = xarVar::prepForDisplay($modinfo['name']); */
/*     $data['moddescr']           = xarVar::prepForDisplay($modinfo['description']); */
/*     $data['moddispname']        = xarVar::prepForDisplay($modinfo['displayname']); */
/*     $data['modlisturl']         = xarController::URL('modules', 'admin', 'list'); */
/*     // check for proper icon, if not found display default */
/*     // also displaying a generic icon now, if it was provided */
/*     // additionally showing a short message if the icon is missing.. */
/*     $modicon = sys::code() . 'modules/'.$modinfo['directory'].'/xarimages/admin.gif'; */
/*     $modicongeneric = sys::code() . 'modules/'.$modinfo['directory'].'/xarimages/admin_generic.gif'; */
/*     if(file_exists($modicon)){ */
/*         $data['modiconurl']     = xarVar::prepForDisplay($modicon); */
/*         $data['modiconmsg'] = xarVar::prepForDisplay(xarML('as provided by the author')); */
/*     }elseif(file_exists($modicongeneric)){ */
/*         $data['modiconurl']     = xarVar::prepForDisplay($modicongeneric); */
/*         $data['modiconmsg'] = xarVar::prepForDisplay(xarML('Only generic icon has been provided')); */
/*     }else{ */
/*         $data['modiconurl']     = xarVar::prepForDisplay('modules/modules/xarimages/admin_generic.gif'); */
/*         $data['modiconmsg'] = xarVar::prepForDisplay(xarML('[Original icon is missing.. please ask this module developer to provide one in accordance with MDG]')); */
/*     } */
/*     $data['moddir']             = xarVar::prepForDisplay($modinfo['directory']); */
/*     $data['modclass']           = xarVar::prepForDisplay($modinfo['class']); */
/*     $data['modcat']             = xarVar::prepForDisplay($modinfo['category']); */
/*     $data['modver']             = xarVar::prepForDisplay($modinfo['version']); */
/*     $data['modauthor']          = preg_replace('/,/', '<br />', xarVar::prepForDisplay($modinfo['author'])); */
/*     $data['modcontact']         = preg_replace('/,/', '<br />',xarVar::prepForDisplay($modinfo['contact'])); */
/*     if(!empty($modinfo['dependency'])){ */
/*         $dependency             = xarML('Working on it...'); */
/*     } else { */
/*         $dependency             = xarML('None'); */
/*     } */
/*     $data['moddependency']      = xarVar::prepForDisplay($dependency); */
    
    // done
    return $data;
}
?>
