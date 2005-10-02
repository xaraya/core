<?php
/**
 * Language Selection via block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/*
 * Language Selection via block
 * @author Marco Canini
 * initialise block
 */
function roles_languageblock_init()
{
    return array(
        'nocache' => 1, // don't cache by default
        'pageshared' => 1, // share across pages
        'usershared' => 0, // don't share across users
        'cacheexpire' => null);
}

/**
 * get information on block
 */
function roles_languageblock_info()
{
    return array(
        'text_type' => 'Language',
        'module' => 'roles',
        'text_type_long' => 'Language selection'
    );
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function roles_languageblock_display($blockinfo)
{
    // Security check
    if (!xarSecurityCheck('ReadRole', 0, 'Block', "All:" . $blockinfo['title'] . ":" . $blockinfo['bid'])) {return;}

    // if (xarMLSGetMode() != XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
    if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE) {
        return;
    }

    $current_locale = xarMLSGetCurrentLocale();

    $site_locales = xarMLSListSiteLocales();

    asort($site_locales);

    if (count($site_locales) <= 1) {
        return;
    }

    foreach ($site_locales as $locale) {
        $locale_data =& xarMLSLoadLocaleData($locale);

        $selected = ($current_locale == $locale);

        $locales[] = array(
            'locale'   => $locale,
            'country'  => $locale_data['/country/display'],
            'name'     => $locale_data['/language/display'],
            'selected' => $selected
        );
    }


    $tplData['form_action'] = xarModURL('roles', 'user', 'changelanguage');
    $tplData['form_picker_name'] = 'locale';
    $tplData['locales'] = $locales;
    $tplData['blockid'] = $blockinfo['bid'];

    if (xarServerGetVar('REQUEST_METHOD') == 'GET') {
        // URL of this page
        $tplData['return_url'] = xarServerGetCurrentURL();
    } else {
        // Base URL of the site
        $tplData['return_url'] = xarServerGetBaseURL();
    }

    $blockinfo['content'] = $tplData;

    return $blockinfo;
}

?>
