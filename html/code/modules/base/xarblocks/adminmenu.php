<?php
/**
 * Base block management
 *
 * @package modules
 * @subpackage base module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Initialise block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
// Inherit properties from MenuBlock class
sys::import('xaraya.structures.containers.blocks.menublock');

class Base_AdminmenuBlock extends MenuBlock implements iBlock
{
    public $name                = 'AdminMenuBlock';
    public $module              = 'base';
    public $text_type           = 'Admin Menu';
    public $text_type_long      = 'Displays Admin Menu';
    public $allow_multiple      = true;
    public $nocache             = 1;

    public $showlogout          = 1;
    public $menustyle           = 'bycat';
    //public $showhelp            = 0; <chris> remove this unused property for now
    public $showfront           = 1;
    public $marker              = '';

    public $menumodtype         = 'admin';
    public $menumodtypes        = array('admin', 'util');

    public $xarversion          = '2.2.0';

    public function __construct(Array $data=array())
    {
        parent::__construct($data);

        if (empty($this->modulelist)) {
            // if the modulelist is empty, admin deselected all modules, put back the modules module
            // @CHECKME: put back the blocks module too so we can edit this?
            $this->modulelist = array('modules' => array('visible' => 1));
        }
        // make sure we keep the content array in sync
        $this->content['modulelist'] = $this->modulelist;

    }
/**
 * This method is called by the BasicBlock class constructor
**/
    public function upgrade($oldversion) {

        switch ($oldversion) {
            case '0.0.0': // upgrade menu blocks to version 2.2.0
            if (!isset($this->content['marker']))
                $this->content['marker'] = $this->marker;
            // fall through to next version
            case '2.2.0':

            break;
        }
        return true;
    }

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;

        $vars = isset($data['content']) ? $data['content'] : array();

        foreach ($this->xarmodules as $mod) {
            $modname = $mod['name'];
            if (!isset($this->modulelist[$modname])) continue;
            $link = $this->modulelist[$modname];
            $link['modname'] = $modname;
            $link = self::getModuleLink($link);
            if (!$link) continue;
            $link['title'] = xarML('Show administration options for module #(1)', $link['label']);
            switch ($vars['menustyle']) {
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

        switch ($vars['menustyle']) {
            case 'byname':
                $vars['adminmods'] = $adminmods;
                $template = 'verticallistbyname';
            break;
            case 'bycat':
                ksort($categories);
                $vars['catmods'] = $categories;
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
        $vars['overviewlink']=$overviewlink;
        */

        // Set template base.
        // FIXME: not allowed to set private variables of BL directly
        $data['_bl_template_base'] = $template;
        $data['content'] = $vars;

        return $data;
    }
}
?>
