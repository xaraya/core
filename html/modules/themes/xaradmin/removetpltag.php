<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Update/insert a template tag
 *
 * @author Marty Vance
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
        throw new TagRegistrationException($tagname,'Could not unregister tag (#(1)).');
    }

    xarResponseRedirect(xarModUrl('themes', 'admin', 'listtpltags'));
    
    return true;
} 

?>
