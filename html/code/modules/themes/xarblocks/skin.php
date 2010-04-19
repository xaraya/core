<?php
/**
 * Skin Selection via block
 *
 * @package Xaraya eXtensible Management System
 * @copyright see the html/credits.html file in this release
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

        $current_theme_name = xarModVars::get('themes', 'default');
        $site_themes = xarMod::apiFunc('themes', 'admin','getthemelist');
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
        $tplData['blockid'] = $data['bid'];
        $tplData['authid'] = xarSecGenAuthKey();

        if (xarServer::getVar('REQUEST_METHOD') == 'GET') {
            // URL of this page
            $tplData['return_url'] = xarServer::getCurrentURL();
        } else {
            // Base URL of the site
            $tplData['return_url'] = xarServer::getBaseURL();
        }

        $data['content'] = $tplData;

        return $data;

    }

}
?>