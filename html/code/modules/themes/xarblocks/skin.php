<?php
/**
 * Skin Selection via block
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
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
    public $nocache             = 1;

    public $name                = 'SkinBlock';
    public $module              = 'themes';
    public $text_type           = 'Skin';
    public $text_type_long      = 'Skin Selection';
    public $pageshared          = 1;

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;
        
        if (!xarUserIsLoggedIn() ||
            (bool) xarModVars::get('themes', 'enable_user_menu') == false) return;
        
        $content = !empty($data['content']) ? $data['content'] : array();
        $content['user_themes'] = xarMod::apiFunc('themes', 'user', 'dropdownlist');
        if ($content['user_themes'] <= 1) return;
        $content['default_theme'] = xarModUserVars::get('themes', 'default_theme');
        $content['return_url'] = (xarServer::getVar('REQUEST_METHOD') == 'GET') ?
            xarServer::getCurrentURL() : xarServer::getBaseURL();

        $data['content'] = $content;

        return $data;

    }

/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);
        $data['enable_user_menu'] = xarModVars::get('themes', 'enable_user_menu');
        return $data;
    }

}
?>
