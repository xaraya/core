<?php
/**
 * Skin Selection via block
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */

/*
 * Skin Selection via block
 * @author Marco Canini
 * initialise block
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Themes_SkinBlock extends BasicBlock implements iBlock
{
    protected $type                = 'skin';
    protected $module              = 'themes';
    protected $text_type           = 'Theme Switcher';
    protected $text_type_long      = 'User Theme Switcher Selection';

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

/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = $this->getContent();
        $data['enable_user_menu'] = xarModVars::get('themes', 'enable_user_menu');
        return $data;
    }

}
?>
