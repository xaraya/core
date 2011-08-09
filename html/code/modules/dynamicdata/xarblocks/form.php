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
    protected $type                = 'form';
    protected $module              = 'dynamicdata';
    protected $text_type           = 'Form';
    protected $text_type_long      = 'Show dynamic data form';
    protected $allow_multiple      = true;
    protected $show_preview        = true;

    public $objectid            = 0;

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display()
    {
        $data = $this->getContent();
        
        if (!empty($data['objectid'])) {
            $object = DataObjectMaster::getObject($data);
            if (!empty($object) && $object->checkAccess('create')) {
                $data['moduleid'] = $object->moduleid;
                $data['itemtype'] = $object->itemtype;
                $data['object'] = $object;
                return $data;
            }
        }
        return;
    }
}
?>
