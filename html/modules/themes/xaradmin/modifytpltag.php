<?php
/**
 * Modify a template tag
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Modify a template tag
 *
 * @author Marty Vance
 * @param none
 */
function themes_admin_modifytpltag()
{
    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0, 'All', '::')) return;
    
    $aData = array();

    // form parameters
    if (!xarVarFetch('tagname', 'str::', $tagname, '')) return;

    // get the tags as an array
    $aTplTag = xarModAPIFunc('themes', 
                             'admin', 
                             'gettpltag', 
                             array('tagname'=>$tagname));

    $aData = $aTplTag;
    $aData['authid'] = xarSecGenAuthKey();
    $aData['updateurl'] = xarModUrl('themes', 
                                    'admin', 
                                    'updatetpltag');

    return $aData;
}

?>