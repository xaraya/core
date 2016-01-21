<?php
/**
 * Adminmenu Block configuration interface
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Manage block config
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
sys::import('modules.base.xarblocks.adminmenu');

class Base_AdminmenuBlockConfig extends Base_AdminmenuBlock implements iBlockModify
{

/**
 * This method is called by the BasicBlock class constructor
 * 
 * @param void N/A
**/    
    public function init()
    {
        parent::init();
    }
    /**
     * Modify Function to the Blocks Admin
     * 
     * @param string $data['title']
     * @param string $data['content']
     * @return array
     */
    public function configmodify(Array $data=array())
    {
        $data = $this->getContent();

        // Admin Capable Modules
        $data['modules'] = $this->xarmodules;

        // Set the template data we need
        $data['sortorder'] = array(
            array('id' => 'byname', 'name' => xarML('By Name')),
            array('id' => 'bycat', 'name' => xarML('By Category')),
        );

        return $data;
    }

    /**
     * Updates the Block config from the Blocks Admin
     * 
     * @param array $data Data array continaing title, content
     * @return boolean Returns true on success, false on failure
     */
    public function configupdate(Array $data=array())
    {
        $data = parent::update($data);

        if (!xarVarFetch('showlogout', 'int:0:1', $showlogout, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('menustyle' , 'pre:trim:lower:enum:byname:bycat' , $menustyle , 'bycat', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showfront', 'int:0:1', $showfront, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('marker',      'str:0',    $marker, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('modulelist', 'array', $modulelist, array(), XARVAR_NOT_REQUIRED)) return;

        if (empty($modulelist)) $modulelist = array('modules' => array('visible' => 1));

        $i = 0;
        foreach ($this->xarmodules as $mod) {
            if (empty($modulelist[$mod['name']]['visible']))
                $modulelist[$mod['name']]['visible'] = 0;
            if (empty($modulelist[$mod['name']]['alias_name']) ||
                empty($this->modulelist[$mod['name']]['aliases']) ||
                !isset($this->modulelist[$mod['name']]['aliases'][$modulelist[$mod['name']]['alias_name']])) {
                $modulelist[$mod['name']]['alias_name'] = $mod['name'];
            }
            if (empty($modulelist[$mod['name']]['order']))
                $modulelist[$mod['name']]['order'] = $i;
            $i++;
        }

        $this->showlogout = $showlogout;
        $this->menustyle = $menustyle;
        $this->showfront = $showfront;
        $this->modulelist = $modulelist;
        $this->marker = $marker;
        return true;
    }
}
?>
