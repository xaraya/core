<?php

/**
 * Adminmenu Block configuration interface
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Manage block config
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */

class Base_AdminmenuBlockConfig extends Base_AdminmenuBlock implements iBlockModify
{
    /**
     * This method is called by the BasicBlock class constructor
     *
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
     * @return array<
     */
    public function configmodify(array $data = [])
    {
        $data = $this->getContent();

        // Admin Capable Modules
        $data['modules'] = $this->xarmodules;

        // Set the template data we need
        $data['sortorder'] = [
            ['id' => 'byname', 'name' => $this->ml('By Name')],
            ['id' => 'bycat', 'name' => $this->ml('By Category')],
        ];

        return $data;
    }

    /**
     * Updates the Block config from the Blocks Admin
     *
     * @param array<string, mixed> $data Data array continaing title, content
     * @return boolean|void Returns true on success, false on failure
     */
    public function configupdate(array $data = [])
    {
        $data = parent::update($data);

        $this->var()->find('showlogout', $showlogout, 'int:0:1', 0);
        $this->var()->find('menustyle', $menustyle, 'pre:trim:lower:enum:byname:bycat', 'bycat');
        $this->var()->find('showfront', $showfront, 'int:0:1', 0);
        $this->var()->find('marker', $marker, 'str:0', '');
        $this->var()->find('modulelist', $modulelist, 'array', []);

        if (empty($modulelist)) {
            $modulelist = ['modules' => ['visible' => 1]];
        }

        $i = 0;
        foreach ($this->xarmodules as $mod) {
            if (empty($modulelist[$mod['name']]['visible'])) {
                $modulelist[$mod['name']]['visible'] = 0;
            }
            if (empty($modulelist[$mod['name']]['alias_name'])
                || empty($this->modulelist[$mod['name']]['aliases'])
                || !isset($this->modulelist[$mod['name']]['aliases'][$modulelist[$mod['name']]['alias_name']])) {
                $modulelist[$mod['name']]['alias_name'] = $mod['name'];
            }
            if (empty($modulelist[$mod['name']]['order'])) {
                $modulelist[$mod['name']]['order'] = $i;
            }
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
