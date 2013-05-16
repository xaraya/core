<?php
/**
 * Adminmenu Block display interface
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Display block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
sys::import('modules.base.xarblocks.adminmenu');

class Base_AdminmenuBlockDisplay extends Base_AdminmenuBlock implements iBlockModify
{

/**
 * This method is called by the BasicBlock class constructor
**/    
    public function init()
    {
        parent::init();
    }

/**
 * Display func.
 * @param $data array containing title,content
 */
    public function display()
    {
        $data = $this->getContent();

        foreach ($this->xarmodules as $mod) {
            $modname = $mod['name'];
            if (!isset($this->modulelist[$modname])) continue;
            $link = $this->modulelist[$modname];
            $link['modname'] = $modname;
            $link = self::getModuleLink($link);
            if (!$link) continue;
            $link['title'] = xarML('Show administration options for module #(1)', $link['label']);
            switch ($data['menustyle']) {
                case 'bycat':
                default:
                    // determine category
                    if(!isset($mod['category']) or $mod['category'] == '0') {
                        $mod['category'] = xarML('Unknown');
                    }
                    $cat = xarVarPrepForDisplay($mod['category']);
                    // add module link to category
                    $categories[$cat][$modname] = $link;
                break;
                case 'byname':
                    // add module link to adminmods
                    $adminmods[$modname] = $link;
                break;
            }
        }

        switch ($data['menustyle']) {
            case 'byname':
                $data['adminmods'] = $adminmods;
                $template = 'verticallistbyname';
            break;
            case 'bycat':
                ksort($categories);
                $data['catmods'] = $categories;
                $template = 'verticallistbycats';
            break;
        }

        //making a few assumptions here for now about modname and directory
        //very rough - but let's use what we have for now
        //Leave way open for real help system
        //TODO : move any final help functions to some module or api when decided
        /* <chris> removing this for now as it isn't used anywhere
        if (file_exists(sys::code() . 'modules/'.$thismodname.'/xaradmin/overview.php')) {
            if ($thisfuncname<>'overview' && $thisfuncname<>'main') {
                $overviewlink = xarModURL($thismodname,'admin','overview',array(),NULL,$thisfuncname);
            } else {
                $overviewlink = xarModURL($thismodname,'admin','overview');
            }
        } else { //no overview exists;
            $overviewlink = xarModURL('base','admin','overview',array('template'=>'nooverview'));
        }
        $data['overviewlink']=$overviewlink;
        */

        // Set template base.
        $this->setTemplateBase($template);

        return $data;
    }

    public function help()
    {
        return $this->getContent();
    }
}
?>
