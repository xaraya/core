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
    if (!xarSecConfirmAuthKey()) return;

    if (!xarSecurityCheck('AdminTheme')) return;

    if (!xarVarFetch('sitename', 'str:1:', $sitename, 'Your Site Name', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('separator', 'str:1:', $separator, ' :: ', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pagetitle', 'str:1:', $pagetitle, 'default', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showtemplates', 'checkbox', $showtemplates, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('cachetemplates', 'checkbox', $cachetemplates, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('var_dump', 'checkbox', $var_dump, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('slogan', 'str::', $slogan, 'Your Site Slogan', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('footer', 'str:1:', $footer, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('copyright', 'str:1:', $copyright, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('AtomTag', 'str:1:', $atomtag, '', XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('dashboard', 'checkbox', $dashboard, false, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('adminpagemenu', 'checkbox', $adminpagemenu, false, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('dashtemplate', 'str:1:', $dashtemplate, 'dashboard', XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('usermenu', 'checkbox', $usermenu, false, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('themedir','str:1:',$defaultThemeDir,'themes',XARVAR_NOT_REQUIRED)) return;

    xarModVars::set('themes', 'themesdirectory', $defaultThemeDir);
    xarModVars::set('themes', 'SiteName', $sitename);
    xarModVars::set('themes', 'SiteTitleSeparator', $separator);
    xarModVars::set('themes', 'SiteTitleOrder', $pagetitle);
    xarModVars::set('themes', 'SiteSlogan', $slogan);
    xarModVars::set('themes', 'SiteCopyRight', $copyright);
    xarModVars::set('themes', 'SiteFooter', $footer);
    xarModVars::set('themes', 'ShowTemplates', $showtemplates);
    xarModVars::set('themes', 'AtomTag', $atomtag);
    xarModVars::set('themes', 'var_dump', $var_dump);
    xarModVars::set('themes', 'usedashboard', ($dashboard) ? 1 : 0);
    xarModVars::set('themes', 'adminpagemenu', ($adminpagemenu) ? 1 : 0);
    xarModVars::set('themes', 'dashtemplate', $dashtemplate);
    xarConfigVars::set(null, 'Site.BL.CacheTemplates',$cachetemplates);

    // make sure we dont miss empty variables (which were not passed thru)
    if (empty($selstyle)) $selstyle = 'plain';
    if (empty($selfilter)) $selfilter = XARMOD_STATE_ANY;
    if (empty($hidecore)) $hidecore = 0;
    if (empty($selsort)) $selsort = 'namedesc';

    xarModVars::set('themes', 'hidecore', $hidecore);
    xarModVars::set('themes', 'selstyle', $selstyle);
    xarModVars::set('themes', 'selfilter', $selfilter);
    xarModVars::set('themes', 'selsort', $selsort);

    // Only go through updatehooks() if there was a change.
    if (xarModIsHooked('themes', 'roles') != $usermenu) {

		sys::import('xaraya.structures.hooks.observer');
		$observer = new BasicObserver('themes','user','usermenu');
	    $subject = new HookSubject('roles');
			$subject->detach($observer);
        if ($usermenu) {
			$subject->attach($observer);
        } else {
        }

        /* Why all this?
        $hooked_roles = array();
        if ($usermenu) {
            $hooked_roles[0] = 1;
            // turning on, so remember previous hook config
            if (xarModIsHooked('themes', 'roles', xarRoles::ROLES_GROUPTYPE)) {
                xarModVars::set('themes', 'group_hooked', true);
            }
        } else {
            // turning off, so restore previous hook config
            if (xarModVars::get('themes', 'group_hooked')) {
                $hooked_roles[0] = 2;
                $hooked_roles[1] = 1; // groups only
                xarModVars::set('themes', 'group_hooked', false);
            } else {
                $hooked_roles[0] = 0; // nothing hooked at all
            }
        }

        // we need to redirect instead of using xarModAPIFunc() because the
        // updatehooks() API function calls xarVarFetch rather than taking
        // input via an $args array.
        $redirecturl = xarModURL('modules', 'admin', 'updatehooks', array(
            'authid' => xarSecGenAuthKey('modules'),
            'curhook' => 'themes',
            'hooked_roles' => $hooked_roles,
            'return_url' => xarModURL('themes', 'admin', 'modifyconfig'),
        ));
        */
    }

    // lets update status and display updated configuration
    $redirecturl = xarModURL('themes', 'admin', 'modifyconfig');
    xarResponseRedirect($redirecturl);

    return true;
}

?>
