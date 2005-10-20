<?php
/**
 * File: $Id$
 *
 * Update/insert a template tag
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
 * Update/insert a template tag
 * 
 * @param tagname 
 * @returns bool
 * @return true on success, error message on failure
 * @author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_admin_removetpltag()
{ 
    // Get parameters
    if (!xarVarFetch('tagname', 'str:1:', $tagname)) return;
    
    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0, 'All', '::')) return;

    if(!xarTplUnregisterTag($tagname)) {
        $msg = xarML('Could not unregister (#(1)).', $tagname);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                        new SystemException($msg));
       return;
    }

    xarResponseRedirect(xarModUrl('themes', 'admin', 'listtpltags'));
    
    return true;
} 

?>