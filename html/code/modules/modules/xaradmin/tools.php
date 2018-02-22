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
    if (!xarSecurityCheck('AdminModules')) return; 
        
    $data = array();
    
/*     if (!xarVarFetch('id', 'id', $id)) {return;} */
/*     // obtain maximum information about module */
/*     $modinfo = xarMod::getInfo($id); */
/*      */
/*     // data vars for template */
/*     $data['modid']              = xarVarPrepForDisplay($id); */
/*     $data['modname']            = xarVarPrepForDisplay($modinfo['name']); */
/*     $data['moddescr']           = xarVarPrepForDisplay($modinfo['description']); */
/*     $data['moddispname']        = xarVarPrepForDisplay($modinfo['displayname']); */
/*     $data['modlisturl']         = xarModURL('modules', 'admin', 'list'); */
/*     // check for proper icon, if not found display default */
/*     // also displaying a generic icon now, if it was provided */
/*     // additionally showing a short message if the icon is missing.. */
/*     $modicon = sys::code() . 'modules/'.$modinfo['directory'].'/xarimages/admin.gif'; */
/*     $modicongeneric = sys::code() . 'modules/'.$modinfo['directory'].'/xarimages/admin_generic.gif'; */
/*     if(file_exists($modicon)){ */
/*         $data['modiconurl']     = xarVarPrepForDisplay($modicon); */
/*         $data['modiconmsg'] = xarVarPrepForDisplay(xarML('as provided by the author')); */
/*     }elseif(file_exists($modicongeneric)){ */
/*         $data['modiconurl']     = xarVarPrepForDisplay($modicongeneric); */
/*         $data['modiconmsg'] = xarVarPrepForDisplay(xarML('Only generic icon has been provided')); */
/*     }else{ */
/*         $data['modiconurl']     = xarVarPrepForDisplay('modules/modules/xarimages/admin_generic.gif'); */
/*         $data['modiconmsg'] = xarVarPrepForDisplay(xarML('[Original icon is missing.. please ask this module developer to provide one in accordance with MDG]')); */
/*     } */
/*     $data['moddir']             = xarVarPrepForDisplay($modinfo['directory']); */
/*     $data['modclass']           = xarVarPrepForDisplay($modinfo['class']); */
/*     $data['modcat']             = xarVarPrepForDisplay($modinfo['category']); */
/*     $data['modver']             = xarVarPrepForDisplay($modinfo['version']); */
/*     $data['modauthor']          = preg_replace('/,/', '<br />', xarVarPrepForDisplay($modinfo['author'])); */
/*     $data['modcontact']         = preg_replace('/,/', '<br />',xarVarPrepForDisplay($modinfo['contact'])); */
/*     if(!empty($modinfo['dependency'])){ */
/*         $dependency             = xarML('Working on it...'); */
/*     } else { */
/*         $dependency             = xarML('None'); */
/*     } */
/*     $data['moddependency']      = xarVarPrepForDisplay($dependency); */
    
    // done
    return $data;
}
?>
