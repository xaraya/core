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
function users_languageblock_init()
{
    // Security
    xarSecAddSchema('users:Languageblock:', 'Block title::');
}

/**
 * get information on block
 */
function users_languageblock_info()
{
    return array('text_type' => 'Language',
                 'module' => 'users',
                 'text_type_long' => 'Language selection');
}

/**
 * display block
 */
function users_languageblock_display($blockinfo)
{
    // Security check
    if (!xarSecAuthAction(0,
                         'users:Languageblock',
                         "$blockinfo[title]::",
                         ACCESS_READ)) {
        return;
    }

    if (xarMLSGetMode() != XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
        return;
    }

    // URL of this page
    $tplData['locales'] = xarMLSListSiteLocales();
    $tplData['return_url'] = xarServerGetCurrentURL();

    $blockinfo['content'] = xarTplBlock('users', 'language', $tplData);
    
    return $blockinfo;
}

?>