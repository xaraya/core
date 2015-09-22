<?php
/**
 * Form Block configuration interface
 *
 * Initialisation and display of the form block
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Manage block config
 */
sys::import('modules.dynamicdata.xarblocks.form');
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
        if (!xarVarFetch('objectid', 'id', $objectid, 0, XARVAR_NOT_REQUIRED)) {return;}
        $this->objectid = $objectid;
        return true;
    }

}
?>