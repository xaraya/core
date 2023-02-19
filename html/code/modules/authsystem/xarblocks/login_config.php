<?php
/**
 * Login Block configuration interface
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 */

/**
 * Manage block config
 *
 * @author Jim McDonald
 * @return array
 */
sys::import('modules.authsystem.xarblocks.login');

/**
 * Authsystem Login Block Configuration
 * 
 * @author Jim McDonald
 */
class Authsystem_LoginBlockConfig extends Authsystem_LoginBlock implements iBlock
{

    /**
     * Method to retrieve block content
     * 
     * @return array Array of block content data
     */
    public function configmodify()
    {
        $data = $this->getContent();
        return $data;
    }

    /**
     * Updates the Block config from the Blocks Admin
     * 
     * @return boolean|void Returns true if configuation was updated successfully
     */
    public function configupdate()
    {
        if (!xarVar::fetch('showlogout',  'checkbox',           $showlogout, false, xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('logouttitle', 'pre:trim:str:1:254', $logouttitle, '', xarVar::NOT_REQUIRED)) return;
        
        $this->showlogout = $showlogout;        
        $this->logouttitle = $logouttitle;
        return true;
    }
}
