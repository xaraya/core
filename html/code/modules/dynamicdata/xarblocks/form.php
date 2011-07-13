<?php
/**
 * Initialisation and display of the form block
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * initialise block
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Dynamicdata_FormBlock extends BasicBlock implements iBlock
{
    public $nocache             = 1;

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
        if (empty($data)) return;

        $vars = isset($data['content']) ? $data['content'] : array();

        if (!isset($vars['objectid'])) $vars['objectid'] = $this->objectid;

        // Populate block info and pass to theme
        if (!empty($vars['objectid'])) {
            $object = DataObjectMaster::getObject($vars);
            if (!empty($object) && $object->checkAccess('create')) {
                $data['content'] = array('moduleid' => $object->moduleid,
                                         'itemtype' => $object->itemtype,
                                         'object'   => $object);
                return $data;
            }
        }
    }
}
?>
