<?php

/**
 * Update the configuration parameters of the
 * module given the information passed back by the modification form
 */
function themes_admin_updateconfig()
{
    // Get parameters

    list($defaulttheme,
         $sitename,
         $slogan,
         $footer,
         $showtemplates,
         $copyright) = xarVarCleanFromInput('defaulttheme',
                                            'sitename',
                                            'slogan',
                                            'footer',
                                            'showtemplates',
                                            'copyright');

    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;

    // Security Check
	if(!xarSecurityCheck('AdminTheme')) return;

    xarModSetVar('themes', 'SiteName', $sitename);
    xarModSetVar('themes', 'SiteSlogan', $slogan);
    xarModSetVar('themes', 'SiteCopyRight', $copyright);
    xarModSetVar('themes', 'SiteFooter', $footer);
    if (!empty($showtemplates)) {
        xarModSetVar('themes', 'ShowTemplates', 1);
    } else {
        xarModSetVar('themes', 'ShowTemplates', 0);
    }

    $whatwasbefore = xarModGetVar('themes', 'default');

    if (!isset($defaulttheme)) {
        $defaulttheme = $whatwasbefore;
    }

    $themeInfo = xarThemeGetInfo($defaulttheme);

    if($themeInfo['class'] != 2){

        xarSessionSetVar('themes_statusmsg', xarML('Selected theme #(1) is not a valid default.',
                        $defaulttheme));
        xarResponseRedirect(xarModURL('themes', 'admin', 'modifyconfig'));
    }

    if (xarVarIsCached('Mod.Variables.themes','default')) {
        xarVarDelCached('Mod.Variables.themes', 'default');
    }

    // update the data
    xarTplSetThemeName($themeInfo['name']);
    xarModSetVar('themes', 'default', $themeInfo['name']);

    // make sure we dont miss empty variables (which were not passed thru)
    if(empty($selstyle)) $selstyle                  = 'plain';
    if(empty($selfilter)) $selfilter                = XARMOD_STATE_ANY;
    if(empty($hidecore)) $hidecore                  = 0;
    if(empty($selsort)) $selsort                    = 'namedesc';

    xarModSetVar('themes', 'hidecore', $hidecore);
    xarModSetVar('themes', 'selstyle', $selstyle);
    xarModSetVar('themes', 'selfilter', $selfilter);
    xarModSetVar('themes', 'selsort', $selsort);


    // lets update status and display updated configuration
    xarSessionSetVar('themes_statusmsg', xarML('Default theme Updated',
                    ' themes'));
    xarResponseRedirect(xarModURL('themes', 'admin', 'modifyconfig'));

    // Return
    return true;
}

?>