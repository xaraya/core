<?php
/**
 * Update the configuration parameters
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Update the configuration parameters of the
 * module given the information passed back by the modification form
 *
 * @author Marty Vance
 */
function themes_admin_updateconfig()
{ 
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return; 
    // Security Check
    if (!xarSecurityCheck('AdminTheme')) return;
    // Get parameters
    if (!xarVarFetch('sitename', 'str:1:', $sitename, 'Your Site Name', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('separator', 'str:1:', $separator, ' :: ', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pagetitle', 'str:1:', $pagetitle, 'default', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showphpcbit', 'checkbox', $showphpcbit, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showtemplates', 'checkbox', $showtemplates, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('cachetemplates', 'checkbox', $cachetemplates, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('var_dump', 'checkbox', $var_dump, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('slogan', 'str::', $slogan, 'Your Site Slogan', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('footer', 'str:1:', $footer, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('copyright', 'str:1:', $copyright, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('AtomTag', 'str:1:', $atomtag, '', XARVAR_NOT_REQUIRED)) return;
    // enable or disable dashboard
    if(!xarVarFetch('dashboard', 'checkbox', $dashboard, false, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dashtemplate', 'str:1:', $dashtemplate, 'dashboard', XARVAR_DONT_SET)) {return;}
    xarModSetVar('themes', 'SiteName', $sitename);
    xarModSetVar('themes', 'SiteTitleSeparator', $separator);
    xarModSetVar('themes', 'SiteTitleOrder', $pagetitle);
    xarModSetVar('themes', 'SiteSlogan', $slogan);
    xarModSetVar('themes', 'SiteCopyRight', $copyright);
    xarModSetVar('themes', 'SiteFooter', $footer);
    xarModSetVar('themes', 'ShowPHPCommentBlockInTemplates', $showphpcbit);
    xarModSetVar('themes', 'ShowTemplates', $showtemplates);
    xarModSetVar('themes', 'AtomTag', $atomtag);
    xarModSetVar('themes', 'var_dump', $var_dump);
    xarModSetVar('themes', 'usedashboard', ($dashboard) ? 1 : 0);
    xarModSetVar('themes', 'dashtemplate', $dashtemplate);
    xarConfigSetVar('Site.BL.CacheTemplates',$cachetemplates);

    // make sure we dont miss empty variables (which were not passed thru)
    if (empty($selstyle)) $selstyle = 'plain';
    if (empty($selfilter)) $selfilter = XARMOD_STATE_ANY;
    if (empty($hidecore)) $hidecore = 0;
    if (empty($selsort)) $selsort = 'namedesc';

    xarModSetVar('themes', 'hidecore', $hidecore);
    xarModSetVar('themes', 'selstyle', $selstyle);
    xarModSetVar('themes', 'selfilter', $selfilter);
    xarModSetVar('themes', 'selsort', $selsort);
    // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('themes', 'admin', 'modifyconfig')); 
    // Return
    return true;
} 

?>
