<?php
/**
 * Online Block
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/**
 * Online Block
 * @author Jim McDonald
 * @author Greg Allan
 * @author John Cox
 * @author Michael Makushev
 * @author Marc Lutolf
 */
sys::import('modules.roles.xarblocks.online');
class Roles_OnlineBlockConfig extends Roles_OnlineBlock
{
	/**
     * Modify the configuration of the online block
     * 
     * @param array $data Data array
     * @return array  array of values to be displayed in the block's configuration page
     */
    function configmodify(Array $data=array())
    {
        return $this->getContent();
    }

	/**
     * Update the configuration of the online block
     * 
     * @return boolean Returns true on success, false on failure
     */
    public function configupdate()
    {
        if (!xarVar::fetch('showusers',     'checkbox', $args['showusers'], false, xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('showusertotal', 'checkbox', $args['showusertotal'], false, xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('showanontotal', 'checkbox', $args['showanontotal'], false, xarVar::NOT_REQUIRED)) return;
        if (!xarVar::fetch('showlastuser',  'checkbox', $args['showlastuser'], false, xarVar::NOT_REQUIRED)) return;
        $this->setContent($args);
        return true;
    }
}
?>