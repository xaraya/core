<?php
/**
 * Online Block
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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

    class Roles_OnlineBlockAdmin extends Roles_OnlineBlock
    {
        function modify(Array $data=array())
        {
            $data = parent::modify($data);
            if (!isset($data['showusers']))     $data['showusers'] = true;
            if (!isset($data['showusertotal'])) $data['showusertotal'] = false;
            if (!isset($data['showanontotal'])) $data['showanontotal'] = false;
            if (!isset($data['showlastuser']))  $data['showlastuser'] = false;
            return $data;
        }

        public function update(Array $data=array())
        {
            $data = parent::update($data);
            if (!xarVarFetch('showusers',     'checkbox', $args['showusers'], false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showusertotal', 'checkbox', $args['showusertotal'], false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showanontotal', 'checkbox', $args['showanontotal'], false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showlastuser',  'checkbox', $args['showlastuser'], false, XARVAR_NOT_REQUIRED)) return;
            $data['content'] = $args;
            return $data;
        }
    }

?>