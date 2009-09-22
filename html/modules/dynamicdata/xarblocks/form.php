<?php
/**
 * Initialisation and display of the form block
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * initialise block
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class FormBlock extends BasicBlock implements iBlock
{
    public $no_cache            = 1;

    public $name                = 'FormBlock';
    public $module              = 'dynamicdata';
    public $text_type           = 'Form';
    public $text_type_long      = 'Show dynamic data form';
    public $allow_multiple      = true;
    public $show_preview        = true;

    public $objectid            = null;

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        // Security check
        if(!xarSecurityCheck('ReadDynamicDataBlock',0,'Block',"$data[title]:All:All")) return;

        $vars = isset($data['content']) ? $data['content'] : array();

        if (!isset($vars['objectid'])) $vars['objectid'] = $this->objectid;

        // Populate block info and pass to theme
        if (!empty($vars['objectid'])) {
            $objectinfo = DataObjectMaster::getObjectInfo($vars);
            if (!empty($objectinfo)) {
                if (!xarSecurityCheck('AddDynamicDataItem',0,'Item',"$objectinfo[moduleid]:$objectinfo[itemtype]:All")) return;
                $data['content'] = $objectinfo;
                return $data;
            }
        }
    }

/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);

        // Defaults
        if (!isset($data['objectid'])) {
            $data['objectid'] = 0;
        }

        $data['blockid'] = $data['bid'];

        // Return output
        return $data;

    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update(Array $data=array())
    {
        $data = parent::update($data);
        if (!xarVarFetch('objectid', 'id', $vars['objectid'], 0, XARVAR_NOT_REQUIRED)) {return;}

        $data['content'] = $vars;

        return $data;
    }

}
?>