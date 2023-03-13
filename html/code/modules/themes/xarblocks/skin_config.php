<?php
/**
 * Skin Block configuration interface
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
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
    
	/**
     * Modify the configuration of the skin block
     * 
     * @return array $data array of values to be displayed in the block's configuration page
     */
    public function configmodify()
    {
        $data = $this->getContent();
        $data['enable_user_menu'] = xarModVars::get('themes', 'enable_user_menu');
        return $data;
    }
	
	/**
     * Update the configuration of the skin block
     * 
     * @return boolean Returns true
     */
    public function configupdate()
    {
        return true;
    }
}
