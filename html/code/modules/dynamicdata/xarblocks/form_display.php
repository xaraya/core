<?php
/**
 * Form Block display interface
 *
 * Initialisation and display of the form block
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Display block
 */
sys::import('modules.dynamicdata.xarblocks.form');
class Dynamicdata_FormBlockDisplay extends Dynamicdata_FormBlock implements iBlock
{
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