<?php
/**
 * Login via a block.
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */

/**
 * Login via a block: manage block
 *
 * @author Jim McDonald
 * @return array
 */
sys::import('modules.authsystem.xarblocks.login');

class LoginBlockAdmin extends LoginBlock implements iBlock
{
/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);
        return $data;

    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update(Array $data=array())
    {
        $data = parent::update($data);
        if (!xarVarFetch('showlogout', 'checkbox', $vars['showlogout'], $this->showlogout, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('logouttitle', 'str', $vars['logouttitle'], $this->logouttitle, XARVAR_NOT_REQUIRED)) return;

        $data['content'] = $vars;

        return $data;
    }
}
?>