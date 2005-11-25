<?php
/**
 * Update/insert a template tag
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
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
function themes_admin_updatetpltag()
{ 
    // Get parameters
    if (!xarVarFetch('tag_name', 'str:1:', $tagname)) return;
    if (!xarVarFetch('tag_module', 'str:1:', $module)) return;
    if (!xarVarFetch('tag_handler', 'str:1:', $handler)) return;
    if (!xarVarFetch('tag_action', 'str:1:', $action)) return;

    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0, 'All', '::')) return;

    if (!xarSecConfirmAuthKey()) return;
    
    // find all attributes (if any)
    $aAttributes = array();
    for ($i=0; $i<10; $i++ ) {
        //xarVarFetch("tag_attrname[$i]", 'isset', $current_attrib);
        if (!xarVarFetch("tag_attrname[$i]", 'isset', $current_attrib,  NULL, XARVAR_DONT_SET)) {return;}
        if (Trim($current_attrib) != '') {
            $aAttributes[] = Trim($current_attrib);
        }
    }

    // action update = delete and re-add
    // action insert = add
    if ($action == 'update') {
        if(!xarTplUnregisterTag($tagname)) {
            $msg = xarML('Could not unregister (#(1)).', $tagname);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                            new SystemException($msg));
        return;
            }
    }
    
    if(!xarTplRegisterTag($module, $tagname, $aAttributes, $handler)) {
        $msg = xarML('Could not register (#(1)).', $tagname);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                        new SystemException($msg));
        return;
    }

    xarResponseRedirect(xarModUrl('themes', 'admin', 'listtpltags'));
    
    return true;
} 

?>
