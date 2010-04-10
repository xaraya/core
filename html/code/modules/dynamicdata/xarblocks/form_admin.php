<?php
/**
 * Initialisation and display of the form block
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Manage block
 */
    sys::import('modules.dynamicdata.xarblocks.form');

class FormBlockAdmin extends FormBlock implements iBlock
{
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