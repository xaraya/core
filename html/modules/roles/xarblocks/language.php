<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Language Selection
// ----------------------------------------------------------------------

/**
 * initialise block
 */
function roles_languageblock_init()
{
    return true;
}

/**
 * get information on block
 */
function roles_languageblock_info()
{
    return array('text_type' => 'Language',
                 'module' => 'roles',
                 'text_type_long' => 'Language selection');
}

/**
 * display block
 */
function roles_languageblock_display($blockinfo)
{
    // Security check
    if (!xarSecurityCheck('ReadRole',1,'Block',"All:" . $blockinfo[title] . ":All")) return;

    if (xarMLSGetMode() != XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
        return;
    }

    $current_locale = xarMLSGetCurrentLocale();

    $site_locales = xarMLSListSiteLocales();

    asort($site_locales);

    foreach ($site_locales as $locale) {
        $locale_data = xarMLSLoadLocaleData($locale);

        $selected = ($current_locale == $locale);

        $locales[] = array('locale'   => $locale,
                           'country'  => $locale_data['/country/display'],
                           'name'     => $locale_data['/language/display'],
                           'selected' => $selected);
    }


    $tplData['form_action'] = xarModURL('roles', 'user', 'changelanguage');
    $tplData['form_picker_name'] = 'locale';
    $tplData['locales'] = $locales;

    // URL of this page
    $tplData['return_url'] = xarServerGetCurrentURL();

    $blockinfo['content'] = xarTplBlock('roles', 'language', $tplData);

    return $blockinfo;
}

?>