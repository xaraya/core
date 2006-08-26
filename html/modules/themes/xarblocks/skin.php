<?php
/**
 * Skin Selection via block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */

/*
 * Skin Selection via block
 * @author Marco Canini
 * initialise block
 */
function themes_skinblock_init()
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
function themes_skinblock_info()
{
    return array(
        'text_type' => 'Skin',
        'module' => 'themes',
        'text_type_long' => 'Skin selection'
    );
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function themes_skinblock_display($blockinfo)
{
    // Security check
//    if (!xarSecurityCheck('ReadTheme', 0, 'Block', "All:" . $blockinfo['title'] . ":" . $blockinfo['bid'])) {return;}

    $current_theme_name = xarModGetVar('themes', 'default');
    $site_themes = xarModAPIFunc('themes', 'admin','getthemelist');
    asort($site_themes);

    if (count($site_themes) <= 1) {
        return;
    }

    foreach ($site_themes as $theme) {
        $selected = ($current_theme_name == $theme['name']);

        $themes[] = array(
            'id'   => $theme['name'],
            'name'     => $theme['name'],
            'selected' => $selected
        );
    }


    $tplData['form_action'] = xarModURL('themes', 'user', 'changetheme');
    $tplData['form_picker_name'] = 'theme';
    $tplData['themes'] = $themes;
    $tplData['blockid'] = $blockinfo['bid'];
    $tplData['authid'] = xarSecGenAuthKey();

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
