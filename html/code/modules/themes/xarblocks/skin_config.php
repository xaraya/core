<?php
/**
 * Skin Block configuration interface
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */

/*
 * Manage block config
 *
 * Skin Selection via block
 * @author Marco Canini
 * initialise block
 */
sys::import('modules.themes.xarblocks.skin');
class Themes_SkinBlockConfig extends Themes_SkinBlock implements iBlock
{
/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function configmodify()
    {
        $data = $this->getContent();
        $data['enable_user_menu'] = xarModVars::get('themes', 'enable_user_menu');
        return $data;
    }
    public function configupdate()
    {
        return true;
    }
}
?>