<?php
/**
 * Skin Block display interface
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/70.html
 */

/*
 * Display block
 *
 * Skin Selection via block
 * @author Marco Canini
 * initialise block
 */
sys::import('modules.themes.xarblocks.skin');
class Themes_SkinBlockDisplay extends Themes_SkinBlock implements iBlock
{
/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        
        if (!xarUserIsLoggedIn() ||
            (bool) xarModVars::get('themes', 'enable_user_menu') == false) return;
        
        $data = $this->getContent();
        $data['user_themes'] = xarMod::apiFunc('themes', 'user', 'dropdownlist');
        if ($data['user_themes'] <= 1) return;
        $data['default_theme'] = xarModUserVars::get('themes', 'default_theme');
        $data['return_url'] = (xarServer::getVar('REQUEST_METHOD') == 'GET') ?
            xarServer::getCurrentURL() : xarServer::getBaseURL();

        return $data;

    }
}
?>