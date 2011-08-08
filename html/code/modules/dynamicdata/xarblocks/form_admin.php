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
 * Manage block
 */
    sys::import('modules.dynamicdata.xarblocks.form');

class Dynamicdata_FormBlockAdmin extends Dynamicdata_FormBlock implements iBlock
{
/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = $this->getContent();

        // Defaults
        if (!isset($data['objectid'])) {
            $data['objectid'] = 0;
        }
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