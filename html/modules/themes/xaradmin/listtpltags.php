<?php
/**
 * File: $Id$
 *
 * List template tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage Themes
 * @author Marty Vance
*/
/**
 * List template tags
 * @param none
 */
function themes_admin_listtpltags()
{
    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0, 'All', '::')) return;
    
    $aData = array();

    // form parameters
    if (!xarVarFetch('modname', 'str:1:', $sSelectedModule, '', XARVAR_NOT_REQUIRED)) return;

    // get the tags as an array
    $aTplTags = xarModAPIFunc('themes', 
                              'admin', 
                              'gettpltaglist', 
                              array('module'=>$sSelectedModule));


    // add delete / edit urls to the array
    for($i=0; $i<sizeOf($aTplTags); $i++) {
        $aTplTags[$i]['editurl']   = xarModUrl('themes', 'admin', 'modifytpltag', array('tagname'=>$aTplTags[$i]['name']));
        $aTplTags[$i]['deleteurl'] = xarModUrl('themes', 'admin', 'removetpltag', array('tagname'=>$aTplTags[$i]['name']));
    }
    
    $aData['tags'] = $aTplTags;
    $aData['addurl'] = xarModUrl('themes', 'admin', 'modifytpltag', array('tagname'=>''));
    
    return $aData;
    
}

?>