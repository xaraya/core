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
    // Security
    xarSecAddSchema('roles:Languageblock:', 'Block title::');
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
    if (!xarSecurityCheck('ReadRole',1,'Block','$blockinfo[title]:All:All')) return;

    if (xarMLSGetMode() != XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
        return;
    }

    // URL of this page
    $tplData['locales'] = xarMLSListSiteLocales();
    $tplData['return_url'] = xarServerGetCurrentURL();

    $blockinfo['content'] = xarTplBlock('roles', 'language', $tplData);

    return $blockinfo;
}

?>