<?php

/**
 * Form Block configuration interface
 *
 * Initialisation and display of the form block
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

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
        $this->var()->find('objectid', $objectid, 'id', 0);
        $this->objectid = $objectid;
        return true;
    }
}
