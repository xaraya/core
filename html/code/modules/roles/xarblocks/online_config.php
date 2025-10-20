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
     * @param array<string, mixed> $data Data array
     * @return array<mixed>  array of values to be displayed in the block's configuration page
     */
    public function configmodify(array $data = [])
    {
        return $this->getContent();
    }

    /**
     * Update the configuration of the online block
     *
     * @return boolean|void Returns true on success, false on failure
     */
    public function configupdate(array $args = [])
    {
        $this->var()->find('showusers', $args['showusers'], 'checkbox', false);
        $this->var()->find('showusertotal', $args['showusertotal'], 'checkbox', false);
        $this->var()->find('showanontotal', $args['showanontotal'], 'checkbox', false);
        $this->var()->find('showlastuser', $args['showlastuser'], 'checkbox', false);
        $this->setContent($args);
        return true;
    }
}
