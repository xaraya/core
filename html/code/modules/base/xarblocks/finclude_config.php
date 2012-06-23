<?php
/**
 * Finclude Block configuration interface
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Manage block config
 *
 * @author Patrick Kellum
 */
sys::import('modules.base.xarblocks.finclude');
class Base_FincludeBlockConfig extends Base_FincludeBlock implements iBlock
{
/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
    public function configmodify(Array $data=array())
    {
        return $this->getContent();
    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
    public function configupdate(Array $data=array())
    {
        if (!xarVarFetch('url', 'pre:trim:str:1:', 
            $url, xarML('Error - No Url Specified'), XARVAR_NOT_REQUIRED)) {return;}

        $this->url = $url;
        return true;
    }
}
?>