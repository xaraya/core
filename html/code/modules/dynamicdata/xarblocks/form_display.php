<?php
/**
 * Form Block display interface
 *
 * Initialisation and display of the form block
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.dynamicdata.xarblocks.form');

/**
 * Display block
 */
class Dynamicdata_FormBlockDisplay extends Dynamicdata_FormBlock implements iBlock
{
    /**
     * Display func.
     * @param $data array containing title,content
     */
    public function display()
    {
        $data = $this->getContent();

        if (!empty($data['objectid'])) {
            $object = DataObjectMaster::getObject($data);
            if (!empty($object) && $object->checkAccess('create')) {
                $data['object'] = $object;
                return $data;
            }
        }
        return;
    }
}
