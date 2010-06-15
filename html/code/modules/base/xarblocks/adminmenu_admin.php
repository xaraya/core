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

        // Admin Capable Modules
        $data['modules'] = $this->adminmodules;

        // Set the template data we need
        $data['sortorder'] = array(
            'byname' => xarML('By Name'),
            'bycat'  => xarML('By Category'),
        );

        return $data;
    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update(Array $data=array())
    {
        $data = parent::update($data);

        if (!xarVarFetch('showlogout', 'int:0:1', $showlogout, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('menustyle' , 'pre:trim:lower:enum:byname:bycat' , $menustyle , 'bycat', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showhelp', 'int:0:1', $showhelp, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showfront', 'int:0:1', $showfront, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('modulelist', 'array', $modulelist, array(), XARVAR_NOT_REQUIRED)) return;

        if (empty($modulelist)) $modulelist = array('modules' => array('visible' => 1));

        // Admin Capable Modules
        $mods = xarMod::apiFunc('modules', 'admin', 'getlist',
            array('filter' => array('AdminCapable' => 1, 'State' => XARMOD_STATE_ACTIVE)));

        foreach ($this->adminmodules as $mod) {
            if (empty($modulelist[$mod['name']]['visible']))
                $modulelist[$mod['name']]['visible'] = 0;
        }

        $vars = $data['content'];
        $vars['showlogout'] = $showlogout;
        $vars['menustyle']  = $menustyle;
        $vars['showhelp']   = $showhelp;
        $vars['showfront']  = $showfront;
        $vars['modulelist'] = $modulelist;

        $data['content'] = $vars;
        return $data;
    }

}
?>
