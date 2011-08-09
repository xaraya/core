<?php
/**
 * Login Block admin interface
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */

/**
 * Login via a block: manage block
 *
 * @author Jim McDonald
 * @return array
 */
sys::import('modules.authsystem.xarblocks.login');

class Authsystem_LoginBlockAdmin extends Authsystem_LoginBlock implements iBlock
{

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update()
    {
        if (!xarVarFetch('showlogout', 'checkbox',
            $showlogout, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('logouttitle', 'pre:trim:str:1:254',
            $logouttitle, '', XARVAR_NOT_REQUIRED)) return;
        
        $this->showlogout = $showlogout;        
        $this->logouttitle = $logouttitle;
        return true;
    }
}
?>