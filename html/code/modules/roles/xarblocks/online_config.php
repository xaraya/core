<?php
/**
 * Online Block
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/27.html
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
    function configmodify(Array $data=array())
    {
        return $this->getContent();
    }

    public function configupdate()
    {
        if (!xarVarFetch('showusers',     'checkbox', $args['showusers'], false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showusertotal', 'checkbox', $args['showusertotal'], false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showanontotal', 'checkbox', $args['showanontotal'], false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showlastuser',  'checkbox', $args['showlastuser'], false, XARVAR_NOT_REQUIRED)) return;
        $this->setContent($args);
        return true;
    }
}
?>