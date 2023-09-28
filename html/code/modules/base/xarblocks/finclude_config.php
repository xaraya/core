<?php
/**
 * Finclude Block configuration interface
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
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
     * Modify function to the blocks admin
     * This method does not apply modifications
     * 
     * @param array<string, mixed> $data Data array for configutation modifications
     * @return string Returns content
     */
    public function configmodify(Array $data=array())
    {
        return $this->getContent();
    }

    /**
     * Updates the Block config from the Blocks Admin
     * 
     * @param array<string, mixed> $data Config data array
     * @return boolean|void Returns true on success, false on failure.
     */
    public function configupdate(Array $data=array())
    {
        if (!xarVar::fetch('url', 'pre:trim:str:1:', 
            $url, xarML('Error - No Url Specified'), xarVar::NOT_REQUIRED)) {return;}

        $this->url = $url;
        return true;
    }
}
