<?php
/**
 * File: $Id$
 *
 * Modify a template tag
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
 * Modify a template tag
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