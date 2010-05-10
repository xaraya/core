<?php
/**
 * Base block management
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Manage block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
sys::import('modules.base.xarblocks.adminmenu');

class Base_AdminmenuBlockAdmin extends Base_AdminmenuBlock implements iBlock
{
/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);

        // Set the template data we need
        $sortorder = array('byname' => xarML('By Name'),
                           'bycat'  => xarML('By Category'));
        $data['sortorder'] = $sortorder;
        return $data;
    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update(Array $data=array())
    {
        $data = parent::update($data);
        if (!xarVarFetch('showlogout', 'int:0:1', $vars['showlogout'], 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('menustyle' , 'str::'  , $vars['menustyle'] , 'bycat', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showhelp', 'int:0:1', $vars['showhelp'], 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showfront', 'int:0:1', $vars['showfront'], 0, XARVAR_NOT_REQUIRED)) return;
        $data['content'] = $vars;
        return $data;
    }

}
?>
