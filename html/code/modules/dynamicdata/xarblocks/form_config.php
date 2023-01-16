<?php
/**
 * Form Block configuration interface
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
 * Manage block config
 */
class Dynamicdata_FormBlockConfig extends Dynamicdata_FormBlock implements iBlock
{
    /**
     * Modify Function to the Blocks Admin
     * @param $data array containing title,content
     */
    public function configmodify()
    {
        return $this->getContent();
    }

    /**
     * Updates the Block config from the Blocks Admin
     * @param $data array containing title,content
     */
    public function configupdate()
    {
        if (!xarVar::fetch('objectid', 'id', $objectid, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        $this->objectid = $objectid;
        return true;
    }
}
